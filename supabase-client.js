// supabase-client.js
// Shared Supabase client — include this on every page AFTER the supabase-js
// CDN script tag. Creates one `supabase` object other scripts on the page
// can use directly (e.g. supabase.from('blips').select('*')).

// Safe to expose publicly: this is the publishable (anon) key, not the
// secret key. It only works within the permissions your RLS policies allow.
const SUPABASE_URL = 'https://zgesikjzjtilnakhymhz.supabase.co';
const SUPABASE_PUBLISHABLE_KEY = 'sb_publishable_qD3rBCb-cUjpIvYeSNktyw_sIwg8kvz';

const supabaseClient = window.supabase.createClient(SUPABASE_URL, SUPABASE_PUBLISHABLE_KEY);

// ---- Avatar upload helper ----
// The avatar picked at signup can't be uploaded right away if email
// confirmation is on — there's no logged-in session yet, and Storage's RLS
// policy requires one. So we stash the picked image in localStorage, and
// call this function again the moment a real session exists (right after a
// successful login). It uploads to the `avatars` bucket under the user's
// own id (as required by the "owners upload avatars" policy), then saves
// the resulting public URL onto that user's profile row.
async function uploadPendingAvatarIfAny(userId) {
  const dataUrl = localStorage.getItem('bb_pending_avatar');
  if (!dataUrl || !userId) return;

  try {
    // data:image/png;base64,xxxx  ->  actual binary Blob
    const res = await fetch(dataUrl);
    const blob = await res.blob();
    const ext = (blob.type.split('/')[1] || 'png').split('+')[0];
    const path = `${userId}/avatar.${ext}`;

    const { error: uploadError } = await supabaseClient
      .storage
      .from('avatars')
      .upload(path, blob, { upsert: true, contentType: blob.type });

    if (uploadError) {
      console.error('Avatar upload failed:', uploadError.message);
      return; // leave it in localStorage, we'll retry on next login
    }

    const { data: publicUrlData } = supabaseClient
      .storage
      .from('avatars')
      .getPublicUrl(path);

    await supabaseClient
      .from('profiles')
      .update({ avatar_url: publicUrlData.publicUrl })
      .eq('id', userId);

    localStorage.removeItem('bb_pending_avatar');
  } catch (err) {
    console.error('Avatar upload error:', err);
    // leave it in localStorage, we'll retry on next login
  }
}
