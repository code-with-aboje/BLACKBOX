This zip has the fixed lobby.html / lobby.css / lobby.js.

What I changed:
- Added a global reset (box-sizing: border-box, html/body margin/padding 0,
  height/width 100%, overflow: hidden) so the page can no longer scroll.
- Switched .lobby-container to position: fixed + height/width: 100% so it's
  locked to the viewport instead of relying on 100vh/100vw (which can cause
  scrollbars on some browsers).
- Scaled down most UI pieces by roughly 20% (profile bar, friend list,
  play button, mode/rank boxes, bottom bar, settings bar) and reduced the
  inline SVG icon width/height attributes to match.

Note: I don't have your actual image assets, so I recreated the empty
Assets/badges, Assets/profile-cards, Assets/profile-icons folders — just
drop your images back in at those same paths and it'll look right.
