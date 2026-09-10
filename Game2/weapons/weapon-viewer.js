import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { MeshoptDecoder } from 'three/addons/libs/meshopt_decoder.module.js';

const container = document.getElementById('weaponArt');

const scene = new THREE.Scene();

const camera = new THREE.PerspectiveCamera(
    45,
    container.clientWidth / container.clientHeight,
    0.1,
    1000
);
camera.position.set(0, 1, 3);

const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
renderer.setSize(container.clientWidth, container.clientHeight);
container.appendChild(renderer.domElement);

const light = new THREE.DirectionalLight(0xffffff, 4);
light.position.set(3, 5, 2);
const ambient = new THREE.AmbientLight(0xffffff, 0.5);
scene.add(light);
scene.add(ambient);

// FIXED: use OrbitControls directly, and target the renderer's canvas
const control = new OrbitControls(camera, renderer.domElement);

const loader = new GLTFLoader();
loader.setMeshoptDecoder(MeshoptDecoder);

let gun;

function loadWeapon(path) {
    if (gun) {
        scene.remove(gun);
        gun = null;
    }
    loader.load(path, (gltf) => {
        gun = gltf.scene;
        gun.position.set(0, -0.4, 0);
        gun.scale.set(0.3, 0.3, 0.3);
        scene.add(gun);
        resetCameraSmooth();
        console.log("Model Added:", path);
    });
}

// Case 1: weapon.js already ran and set this before we got here — load it now
if (window.currentWeaponInfo) {
    loadWeapon(window.currentWeaponInfo.modelPath);
}

// Case 2: future changes (clicking a different weapon/tab) — listen normally
window.addEventListener("weaponChanged", (e) => {
    loadWeapon(e.detail.modelPath);
});

// Listen for weapon.js telling us the selection changed
window.addEventListener("weaponChanged", (e) => {
    loadWeapon(e.detail.modelPath);
});
function animate(){
    requestAnimationFrame(animate);
    control.update();
    control.autoRotate = true;
    control.autoRotateSpeed = 5; // default is 2
    renderer.render(scene, camera);
    // if(gun) gun.position.z += 0.0;
}

animate();