import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { MeshoptDecoder } from 'three/addons/libs/meshopt_decoder.module.js';

// ---------- CONFIG ----------
// Adjust this if your folder structure differs.
// Assumes: Game/lobby.html loading from a sibling "Characters" folder.
const MODEL_PATH = '../Characters/first_character_optimized.glb';
const TARGET_HEIGHT = 1.3;

// ---------- DOM ----------
const container = document.getElementById('character-preview');
if (!container) {
  console.error('character-viewer.js: #character-preview element not found in the page.');
}

// ---------- SCENE ----------
const scene = new THREE.Scene();

const camera = new THREE.PerspectiveCamera(
  45,
  container.clientWidth / container.clientHeight,
  0.1,
  1000
);
camera.position.set(0, 1.5, 3);

const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
renderer.setSize(container.clientWidth, container.clientHeight);
renderer.setPixelRatio(window.devicePixelRatio);
renderer.outputColorSpace = THREE.SRGBColorSpace;
container.appendChild(renderer.domElement);

// ---------- LIGHTING ----------
scene.add(new THREE.AmbientLight(0xffffff, 0.6));

const keyLight = new THREE.DirectionalLight(0xffffff, 1.2);
keyLight.position.set(2, 4, 3);
scene.add(keyLight);

const fillLight = new THREE.DirectionalLight(0xffffff, 0.5);
fillLight.position.set(-2, 2, -2);
scene.add(fillLight);

// ---------- CONTROLS (horizontal rotation only, slowed down) ----------
// const controls = new OrbitControls(camera, renderer.domElement);
const dragZone = document.getElementById('rotate-hitzone') || container;
const controls = new OrbitControls(camera, dragZone);
controls.enableDamping = true;
controls.minPolarAngle = Math.PI / 2;
controls.maxPolarAngle = Math.PI / 2;
controls.rotateSpeed = 1;

// ---------- ANIMATION ----------
let mixer = null;
const clock = new THREE.Clock();

// ---------- SKINNED-MESH-SAFE BOUNDING BOX ----------
function getPosedBoundingBox(root) {
  const box = new THREE.Box3();
  const v = new THREE.Vector3();
  let hasSkinned = false;

  root.updateMatrixWorld(true);

  root.traverse((child) => {
    if (child.isSkinnedMesh) {
      hasSkinned = true;
      child.skeleton.update();
      const posAttr = child.geometry.attributes.position;
      for (let i = 0; i < posAttr.count; i++) {
        v.fromBufferAttribute(posAttr, i);
        child.applyBoneTransform(i, v);
        v.applyMatrix4(child.matrixWorld);
        box.expandByPoint(v);
      }
    } else if (child.isMesh) {
      box.expandByObject(child);
    }
  });

  if (!hasSkinned && box.isEmpty()) {
    box.setFromObject(root);
  }

  return box;
}

function frameCameraToModel(size) {
  const maxDim = Math.max(size.x, size.y, size.z);
  const fovRad = camera.fov * (Math.PI / 180);
  const distance = (maxDim / 2) / Math.tan(fovRad / 2) * 1.4;

  camera.position.set(0, size.y * 0.5, distance);
  camera.lookAt(0, size.y * 0.5, 0);
  controls.target.set(0, size.y * 0.5, 0);
  controls.update();
}

// ---------- LOAD MODEL ----------
const loader = new GLTFLoader();
loader.setMeshoptDecoder(MeshoptDecoder);

loader.load(
  MODEL_PATH,
  (gltf) => {
    const model = gltf.scene;
    scene.add(model);

    let box = getPosedBoundingBox(model);
    let size = box.getSize(new THREE.Vector3());

    if (size.y === 0 || !isFinite(size.y)) {
      console.error('character-viewer.js: could not compute a valid bounding box for the model.');
      window.dispatchEvent(new CustomEvent('model-ready')); // hide overlay even on failure
      return;
    }

    const scaleFactor = TARGET_HEIGHT / size.y;
    model.scale.setScalar(scaleFactor);

    box = getPosedBoundingBox(model);
    size = box.getSize(new THREE.Vector3());
    const center = box.getCenter(new THREE.Vector3());

    model.position.x -= center.x;
    model.position.y -= box.min.y;
    model.position.z -= center.z;

    frameCameraToModel(size);

    window.dispatchEvent(new CustomEvent('model-ready'));

    if (gltf.animations && gltf.animations.length > 0) {
      mixer = new THREE.AnimationMixer(model);
      const clip = gltf.animations[0]; // change index/name to pick a different clip
      mixer.clipAction(clip).play();
    }
  },
  (event) => {
    // fires repeatedly while the .glb downloads (only accurate if the server sends Content-Length)
    if (event.lengthComputable) {
      const percent = (event.loaded / event.total) * 100;
      window.dispatchEvent(new CustomEvent('model-progress', { detail: { percent } }));
    }
  },
  (error) => {
    console.error('character-viewer.js: model failed to load:', error);
    window.dispatchEvent(new CustomEvent('model-ready')); // hide overlay even on failure
  }
);

// ---------- RENDER LOOP ----------
function animate() {
  requestAnimationFrame(animate);
  const delta = clock.getDelta();
  if (mixer) mixer.update(delta);
  controls.update();
  renderer.render(scene, camera);
}
animate();

// ---------- RESIZE ----------
window.addEventListener('resize', () => {
  if (container.clientWidth === 0 || container.clientHeight === 0) return;
  camera.aspect = container.clientWidth / container.clientHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(container.clientWidth, container.clientHeight);
});
