@extends('admin::components.layouts.master')

@section('content')
<!-- qo'shilgan 1 -->
    <style>
        /* Profile page only: change cursor to 😈 */
        body {
            cursor: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32'%3E%3Ctext x='50%25' y='50%25' dy='.35em' text-anchor='middle' font-family='Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, EmojiOne Color, sans-serif' font-size='28'%3E%26%23128520%3B%3C/text%3E%3C/svg%3E") 16 16, auto;
        }
    </style>
<!-- yakunidan 1 -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Profile</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Profile</li>
            </ul>
        </div>
    </div>

    <div class=" mb-5 mt-1 ms-4 me-4">
        <div class=" mt-4">
<!-- qo'shilgan 2             -->
            <div class="card stretch" data-fly-arena>
<!-- yakunidan 2                 -->
                <div class="card-body text-center">
                    <div class="avatar-text avatar-xxl mx-auto mb-3">
                        <img src="{{ $user->avatar_path ? asset($user->avatar_path) : module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/5.svg') }}" alt="" class="img-fluid">
                    </div>
<!-- qo'shilgan 3                     -->
                    <div class="mb-3">
                       <button class="btn btn-sm  me-2 bg-red">😈😈😈</button>
                    </div>
                    <div class="mt-5">
                       <button class="btn btn-sm text-white justify-items-end-safe bg-red">STOP</button>
                    </div>
<!-- yakunidan 3                     -->
                    <h5 class="fw-bold text-dark mb-1">{{ $user->first_name ?? '' }} {{ $user->last_name ?? '' }}</h5>
                    <p class="text-muted mb-2">{{ $user->role->name ?? '—' }}</p>
                    <p class="mb-0"><i class="feather-mail me-1"></i> {{ $user->email }}</p>
                    @if($user->phone)
                        <p class="mb-0"><i class="feather-phone me-1"></i> {{ $user->phone }}</p>
                    @endif
                    <div class="row mt-4 g-2">
                        <div class="col-4">
                            <div class="border rounded py-2">
                                <div class="fs-6 fw-bold text-dark">{{ $resumesCount }}</div>
                                <div class="fs-11 text-muted">Resumes</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded py-2">
                                <div class="fs-6 fw-bold text-dark">{{ $applicationsCount }}</div>
                                <div class="fs-11 text-muted">Applications</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded py-2">
                                <div class="fs-6 fw-bold text-dark">{{ $profileViewsCount }}</div>
                                <div class="fs-11 text-muted">Views</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="badge bg-primary">Credits: {{ $user->credit->balance ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class=" mt-4">
<!-- qo'shilgan 4 -->
            <div class="card stretch" data-fly-arena>
<!-- yakunidan 4                 -->
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">Account Details</h6></div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">First Name</label>
                            <div class="form-control">{{ $user->first_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Last Name</label>
                            <div class="form-control">{{ $user->last_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Email</label>
                            <div class="form-control">{{ $user->email ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Phone</label>
                            <div class="form-control">{{ $user->phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Birth Date</label>
                            <div class="form-control">{{ $user->birth_date ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Role</label>
                            <div class="form-control">{{ $user->role->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
<!-- qo'shilgan 5 -->
            <div class="card stretch mb-4" data-fly-arena>
<!-- yakunidan 5                 -->
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">Settings</h6></div>
                </div>
                <div class="card-body mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Language</label>
                            <div class="form-control">{{ $user->settings->language ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Notifications</label>
                            <div class="form-control">{{ ($user->settings->notifications_enabled ?? false) ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Auto Apply</label>
                            <div class="form-control">{{ ($user->settings->auto_apply_enabled ?? false) ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Auto Apply Limit</label>
                            <div class="form-control">{{ $user->settings->auto_apply_limit ?? 0 }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Auto Apply Count</label>
                            <div class="form-control">{{ $user->settings->auto_apply_count ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- qo'shilgan 6 -->

    <style>
        /* Page-only cursor: evil smiley */
        body { cursor: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32'%3E%3Ctext x='50%25' y='50%25' dy='.35em' text-anchor='middle' font-family='Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, EmojiOne Color, sans-serif' font-size='28'%3E%26%23128520%3B%3C/text%3E%3C/svg%3E") 16 16, auto; }
        /* Fly overlay per card */
        [data-fly-arena] { position: relative; }
        .fly-canvas { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5; display: block; }
        /* Global cursor serpent overlay */
        #cursor-serpent { position: fixed; inset: 0; width: 100vw; height: 100vh; pointer-events: none; z-index: 2147483646; display: block; }
    </style>
    <script>
        (function(){
            const arenas = Array.from(document.querySelectorAll('[data-fly-arena]')).slice(0,3);
            if (!arenas.length) return;

            const dpr = Math.min(2, window.devicePixelRatio || 1);

            function makeCanvas(host){
                const c = document.createElement('canvas');
                c.className = 'fly-canvas';
                host.appendChild(c);
                const ctx = c.getContext('2d');
                function resize(){
                    const r = host.getBoundingClientRect();
                    const w = Math.max(1, Math.floor(r.width));
                    const h = Math.max(1, Math.floor(r.height));
                    if (c.width !== w*dpr || c.height !== h*dpr){
                        c.width = w*dpr; c.height = h*dpr; c.style.width = w+'px'; c.style.height = h+'px';
                        ctx.setTransform(dpr,0,0,dpr,0,0);
                    }
                }
                resize();
                return { c, ctx, resize };
            }

            function buildSurfaces(host){
                const base = host.getBoundingClientRect();
                const els = Array.from(host.querySelectorAll('*'));
                const surfs = [];
                for (const el of els){
                    const r = el.getBoundingClientRect();
                    if (r.height > 18 && r.width > 40 && r.top > base.top + 8 && r.bottom < base.bottom - 4){
                        surfs.push({ x1: r.left - base.left + 6, x2: r.right - base.left - 6, y: r.top - base.top });
                    }
                }
                // also bottom edge as ground
                surfs.push({ x1: 0, x2: base.width, y: base.height - 2 });
                return surfs;
            }

            function spawnFlies(count, w, h){
                const flies = [];
                for (let i=0;i<count;i++){
                    flies.push({
                        x: Math.random()*w, y: Math.random()*h*0.6 + 8,
                        vx: (Math.random()*2-1)*0.6, vy:(Math.random()*2-1)*0.6,
                        state: 'air', // 'air'|'land'
                        t: 0, target: null, landFor: 0,
                        size: 2 + Math.random()*1.2, hue: Math.floor(Math.random()*360)
                    });
                }
                return flies;
            }

            function stepArena(pack){
                const { host, ctx, getRect, getSurfaces, flies } = pack;
                const r = getRect();
                const W = r.width, H = r.height;
                // Occasionally refresh surfaces (layout may shift)
                if ((pack.frameCount++ % 20) === 0) pack.surfaces = getSurfaces();
                const surfaces = pack.surfaces;

                ctx.clearRect(0,0,W,H);
                for (const f of flies){
                    if (f.state === 'dead') continue;
                    if (f.state === 'air'){
                        // Pick target occasionally: either a random point or a landing spot on a surface
                        if (!f.target || Math.random()<0.01){
                            if (Math.random() < 0.35 && surfaces.length){
                                const s = surfaces[Math.floor(Math.random()*surfaces.length)];
                                const tx = s.x1 + Math.random()*(s.x2-s.x1);
                                const ty = s.y - 1;
                                f.target = {x: tx, y: ty, land: true};
                            } else {
                                f.target = { x: Math.random()*W, y: Math.random()*H*0.8 + 4, land: false };
                            }
                        }
                        const tx = f.target.x, ty = f.target.y;
                        const dx = tx - f.x, dy = ty - f.y; const d = Math.hypot(dx,dy) || 1;
                        // Steer towards target with noise
                        f.vx += (dx/d)*0.15 + (Math.random()-0.5)*0.12;
                        f.vy += (dy/d)*0.15 + (Math.random()-0.5)*0.12;
                        // Optional gravity boost (Party/Storm modes)
                        if (window.__flyGravityBoost) f.vy += window.__flyGravityBoost;
                        // Limit speed
                        const sp = Math.hypot(f.vx,f.vy) || 1; const max = 1.4;
                        if (sp > max){ f.vx = f.vx/sp*max; f.vy = f.vy/sp*max; }
                        f.x += f.vx; f.y += f.vy;
                        // Borders (within card)
                        if (f.x < 2){ f.x=2; f.vx=Math.abs(f.vx); }
                        if (f.x > W-2){ f.x=W-2; f.vx=-Math.abs(f.vx); }
                        if (f.y < 2){ f.y=2; f.vy=Math.abs(f.vy); }
                        if (f.y > H-2){ f.y=H-2; f.vy=-Math.abs(f.vy); }
                        // Land if close to landing target and exactly above its y
                        if (f.target.land && Math.abs(f.x - tx) < 3 && Math.abs(f.y - ty) < 3){
                            f.state = 'land'; f.landFor = 60 + (Math.random()*120|0); f.vx = f.vy = 0; f.x = tx; f.y = ty;
                        }
                    } else {
                        // Landed: small wing twitch, countdown then takeoff
                        f.landFor--; if (f.landFor <= 0){ f.state='air'; f.target=null; }
                    }

                    // Update global viewport coords for serpent
                    const base = host.getBoundingClientRect();
                    f.gx = base.left + f.x; f.gy = base.top + f.y;

                    // Draw fly
                    ctx.save();
                    ctx.translate(f.x, f.y);
                    const ang = Math.atan2(f.vy||0.0001, f.vx||0.0001);
                    ctx.rotate(ang);
                    // body
                    ctx.fillStyle = `hsl(${f.hue} 60% 30%)`;
                    ctx.beginPath(); ctx.ellipse(0,0, f.size, f.size*0.7, 0, 0, Math.PI*2); ctx.fill();
                    // head
                    ctx.fillStyle = '#333'; ctx.beginPath(); ctx.arc(-f.size*1.1, 0, f.size*0.55, 0, Math.PI*2); ctx.fill();
                    // wings
                    const flap = (Math.sin((performance.now()+f.hue*10)/80)+1)*0.5;
                    ctx.globalAlpha = 0.6;
                    ctx.fillStyle = 'rgba(200,200,255,0.6)';
                    ctx.beginPath(); ctx.ellipse(-0.2, -f.size*0.8, f.size*0.9, f.size*0.5*(0.6+0.4*flap), -0.3, 0, Math.PI*2); ctx.fill();
                    ctx.beginPath(); ctx.ellipse(-0.2,  f.size*0.8, f.size*0.9, f.size*0.5*(0.6+0.4*(1-flap)), 0.3, 0, Math.PI*2); ctx.fill();
                    ctx.restore();
                }
            }

            // Ensure global registry exists for the serpent
            window.__fliesShared = window.__fliesShared || [];

            const packs = arenas.map(host => {
                const { c, ctx, resize } = makeCanvas(host);
                const pack = {
                    host,
                    ctx,
                    frameCount: 0,
                    getRect: () => host.getBoundingClientRect(),
                    getSurfaces: () => buildSurfaces(host),
                    surfaces: [],
                    flies: spawnFlies(400, host.clientWidth, host.clientHeight)
                };
                // Register flies globally for the serpent to track and eat
                pack.flies.forEach(f => { try { f.host = host; window.__fliesShared.push(f); } catch(e) {} });
                const loop = () => { resize(); stepArena(pack); requestAnimationFrame(loop); };
                requestAnimationFrame(loop);
                return pack;
            });
            // expose packs so button effects can access
            window.__flyPacks = packs;
        })();
        // Cursor-following serpent (single line, fiery edges, slower than cursor)
        (function(){
            const dpr = Math.min(2, window.devicePixelRatio||1);
            const canvas = document.createElement('canvas');
            canvas.id = 'cursor-serpent';
            document.body.appendChild(canvas);
            const ctx = canvas.getContext('2d');
            function resize(){ canvas.width = innerWidth*dpr; canvas.height = innerHeight*dpr; canvas.style.width = innerWidth+'px'; canvas.style.height = innerHeight+'px'; ctx.setTransform(dpr,0,0,dpr,0,0); }
            resize(); addEventListener('resize', resize);

            // Trail points
            const trail = []; let snakeLen = 60; let thickness = 1.0; // bead snake length & thickness (grow on eat)
            let cx = innerWidth/2, cy = innerHeight/2; // cursor
            let px = cx, py = cy; // previous cursor
            let hx = cx, hy = cy; // serpent head
            const OFFSET = 56; // ~1.5cm behind cursor (96px/in ~ 37.8px/cm)
            const LERP = 0.06; // slower smoothing (~6% per frame)
            const MAX_STEP = 4; // px/frame cap for head movement

            addEventListener('mousemove', e=>{ cx=e.clientX; cy=e.clientY; }, {passive:true});
            // Initialize full trail so length stays constant from the start
            for (let i=0;i<snakeLen;i++) trail.push({x:hx, y:hy});

            function step(){
                // compute direction of cursor motion
                const vx = cx - px, vy = cy - py; const vlen = Math.hypot(vx,vy) || 1;
                const tx = cx - (vx/vlen)*OFFSET; const ty = cy - (vy/vlen)*OFFSET;
                // move head with smoothing and max step cap
                let mX = (tx - hx) * LERP;
                let mY = (ty - hy) * LERP;
                const mLen = Math.hypot(mX, mY);
                if (mLen > MAX_STEP){ const s = MAX_STEP / (mLen || 1); mX *= s; mY *= s; }
                hx += mX; hy += mY;

                trail.unshift({x: hx, y: hy});
                while (trail.length > snakeLen) trail.pop();
                px = cx; py = cy;

                // draw
                ctx.clearRect(0,0,innerWidth,innerHeight);
                if (trail.length > 1){
                    // Eat flies if close to any of the first N beads (helps because head trails cursor)
                    const flies = window.__fliesShared || [];
                    const checkBeads = Math.min(25, trail.length); // beads to check
                    let changed = false;
                    for (const f of flies){
                        if (!f || f.state === 'dead') continue;
                        const gx = (typeof f.gx === 'number') ? f.gx : -99999;
                        const gy = (typeof f.gy === 'number') ? f.gy : -99999;
                        // 1) Quick check: if cursor is right on the fly, count as eaten
                        const dcx = gx - cx, dcy = gy - cy;
                        let eaten = (dcx*dcx + dcy*dcy) <= 18*18; // ~18px radius around cursor
                        // 2) Otherwise, check early beads of the snake body (head trails cursor)
                        for (let k=0; k<checkBeads && !eaten; k++){
                            const t = k/(trail.length-1);
                            const bead = trail[k];
                            const r = ((10 + 8*(1-t)) * thickness) + 10; // generous radius
                            const dx = gx - bead.x; const dy = gy - bead.y;
                            if (dx*dx + dy*dy <= r*r){ eaten = true; break; }
                        }
                        if (eaten){ f.state = 'dead'; changed = true; }
                    }
                    if (changed){
                        // Grow slightly once per frame if at least one was eaten
                        snakeLen = Math.min(220, snakeLen + 4);
                        thickness = Math.min(1.8, thickness + 0.06);
                        // Compact global list to alive only so they don't get checked again
                        window.__fliesShared = flies.filter(ff => ff && ff.state !== 'dead');
                    }
                    // Bead snake: tapered orange gradient with simple head (like screenshot)
                    const h0 = trail[0]; const h1 = trail[1] || h0;
                    const hang = Math.atan2(h0.y - h1.y, h0.x - h1.x);
                    // Draw tail to head so head sits on top
                    for (let i = trail.length - 1; i >= 0; i--){
                        const t = i/(trail.length-1); // 0=head, 1=tail
                        const p = trail[i];
                        const r = (4 + 9*(1-t)) * thickness; // smaller tail, bigger head
                        // orange gradient: darker tail, lighter head
                        const hue = 24; // orange
                        const sat = 85;
                        const light = 28 + (1-t)*28; // 28%..56%
                        ctx.globalCompositeOperation='source-over';
                        ctx.shadowBlur = 0;
                        ctx.fillStyle = `hsl(${hue} ${sat}% ${light}%)`;
                        ctx.beginPath(); ctx.arc(p.x,p.y,r,0,Math.PI*2); ctx.fill();
                    }
                    // Head details (eyes + small horns)
                    {
                        const head = trail[0];
                        const nx = -Math.sin(hang), ny = Math.cos(hang);
                        const headR = (4 + 9*(1-0)) * thickness;
                        // eyes
                        const eyeOff = 5*thickness, eyeSep = 5*thickness;
                        const ex1 = head.x + Math.cos(hang)*eyeOff - Math.sin(hang)*eyeSep;
                        const ey1 = head.y + Math.sin(hang)*eyeOff + Math.cos(hang)*eyeSep;
                        const ex2 = head.x + Math.cos(hang)*eyeOff + Math.sin(hang)*eyeSep;
                        const ey2 = head.y + Math.sin(hang)*eyeOff - Math.cos(hang)*eyeSep;
                        ctx.fillStyle = '#fff';
                        ctx.beginPath(); ctx.arc(ex1, ey1, 2.0*thickness, 0, Math.PI*2); ctx.arc(ex2, ey2, 2.0*thickness, 0, Math.PI*2); ctx.fill();
                        ctx.fillStyle = '#111';
                        ctx.beginPath(); ctx.arc(ex1, ey1, 1.0*thickness, 0, Math.PI*2); ctx.arc(ex2, ey2, 1.0*thickness, 0, Math.PI*2); ctx.fill();
                        // small horns (triangles)
                        const base = 8*thickness;
                        function horn(sign){
                            const bx = head.x + nx*base*sign;
                            const by = head.y + ny*base*sign;
                            const tipx = bx + Math.cos(hang - sign*0.6) * 10*thickness;
                            const tipy = by + Math.sin(hang - sign*0.6) * 10*thickness;
                            ctx.fillStyle = 'hsl(18 70% 45%)';
                            ctx.beginPath();
                            ctx.moveTo(bx, by);
                            ctx.lineTo(tipx, tipy);
                            ctx.lineTo(bx + Math.cos(hang - sign*1.3) * 6*thickness, by + Math.sin(hang - sign*1.3) * 6*thickness);
                            ctx.closePath(); ctx.fill();
                        }
                        horn(+1); horn(-1);
                    }
                }
                requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        })();
    </script>
    <style>
        @keyframes subtle-shake {
            0%,100% { transform: translate3d(0,0,0); }
            20% { transform: translate3d(-4px, 2px,0) rotate(-0.5deg); }
            40% { transform: translate3d(3px, -2px,0) rotate(0.5deg); }
            60% { transform: translate3d(-3px, 3px,0) rotate(-0.6deg); }
            80% { transform: translate3d(2px, -3px,0) rotate(0.4deg); }
        }
        .shake-it { animation: subtle-shake 600ms ease-in-out; }
        .fx-overlay { position: fixed; inset:0; pointer-events:none; z-index: 2147483645; }
        /* Hanging cards (window vibe) */
        [data-fly-arena].hang {
            /* Nail position is configurable via CSS vars */
            --nail-x: 12px;
            --nail-y: 12px;
            --rope-len: 22px;
            transform-origin: var(--nail-x) var(--nail-y);
            will-change: transform;
            position: relative; /* for the nail pseudo elements */
            overflow: visible;
            transition: filter .2s ease;
        }
        /* Galaxy background behind cards (real photo) */
        #galaxy-bg { position: fixed; inset: 0; z-index: 0; pointer-events: none; display:block;
            background-image: url('https://images.unsplash.com/photo-1462331940025-496dfbfc7564?auto=format&fit=crop&w=1920&q=80');
            background-size: cover; background-position: center; background-repeat: no-repeat;
            filter: brightness(0.55) contrast(1.15) saturate(1.05);
            transform: translateZ(0); /* avoid repaint issues */
        }
        .nxl-container, .nxl-content, main.nxl-container { position: relative; z-index: 1; }
        [data-fly-arena].hang::before { /* nail */
            content: '';
            position: absolute;
            left: var(--nail-x); top: var(--nail-y);
            transform: translate(-50%, -50%);
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #4b5563;
            box-shadow: 0 0 0 2px #111 inset;
            z-index: 2;
        }
        [data-fly-arena].hang::after { /* short hook/rope */
            content: '';
            position: absolute;
            left: var(--nail-x); top: calc(var(--nail-y) - var(--rope-len));
            transform: translateX(-50%);
            width: 2px; height: var(--rope-len);
            background: linear-gradient(#9ca3af, #6b7280);
            border-radius: 2px;
            transform-origin: bottom center;
        }
    </style>
    <script>
        (function(){
            const btn = Array.from(document.querySelectorAll('button.bg-red')).find(b=>/😈/.test(b.textContent||''));
            if (!btn) return;

            function partyMode(durationSec=15){
                const packs = window.__flyPacks || [];
                const shared = window.__fliesShared || [];
                packs.forEach(pack => {
                    try {
                        const host = pack.host; const w = host.clientWidth, h = host.clientHeight;
                        for (let i=0;i<120;i++){
                            const f = { x: Math.random()*w, y: Math.random()*h*0.8+4, vx:(Math.random()*2-1)*0.8, vy:(Math.random()*2-1)*0.8, state:'air', t:0, target:null, landFor:0, size: 2+Math.random()*1.2, hue: Math.floor(Math.random()*360), host };
                            pack.flies.push(f); shared.push(f);
                        }
                    } catch(e) {}
                });
                window.__fliesShared = shared;
                // Turn on gravity storm a little
                const prev = window.__flyGravityBoost||0; window.__flyGravityBoost = (prev||0) + 0.35;
                setTimeout(()=>{ window.__flyGravityBoost = prev; }, durationSec*1000);
            }

            function gravityStorm(sec=5){
                const prev = window.__flyGravityBoost||0; window.__flyGravityBoost = (prev||0) + 0.6;
                setTimeout(()=>{ window.__flyGravityBoost = prev; }, sec*1000);
            }

            function fireworks(ms=3000){
                const c = document.createElement('canvas'); c.className='fx-overlay'; document.body.appendChild(c);
                const dpr = Math.min(2, window.devicePixelRatio||1); const ctx=c.getContext('2d');
                function resize(){ c.width=innerWidth*dpr; c.height=innerHeight*dpr; c.style.width=innerWidth+'px'; c.style.height=innerHeight+'px'; ctx.setTransform(dpr,0,0,dpr,0,0);} resize();
                let particles=[]; function burst(x,y){ for(let i=0;i<60;i++){ const a=Math.random()*Math.PI*2; const s=Math.random()*3+1; particles.push({x,y,vx:Math.cos(a)*s,vy:Math.sin(a)*s,life:1,color:`hsl(${(Math.random()*360)|0} 90% 60%)`}); } }
                const timer=setInterval(()=>burst(Math.random()*innerWidth, Math.random()*innerHeight*0.6+40), 350);
                let stopTime=Date.now()+ms;
                (function loop(){ ctx.clearRect(0,0,innerWidth,innerHeight); particles.forEach(p=>{ p.x+=p.vx; p.y+=p.vy; p.vy+=0.02; p.life*=0.96; ctx.globalAlpha=p.life; ctx.fillStyle=p.color; ctx.beginPath(); ctx.arc(p.x,p.y,2,0,6.28); ctx.fill(); }); particles=particles.filter(p=>p.life>0.05); if(Date.now()<stopTime){ requestAnimationFrame(loop);} else { clearInterval(timer); c.remove(); } })();
            }

            function confettiShake(ms=1800){
                document.body.classList.add('shake-it'); setTimeout(()=>document.body.classList.remove('shake-it'), 600);
                const c = document.createElement('canvas'); c.className='fx-overlay'; document.body.appendChild(c);
                const dpr = Math.min(2, window.devicePixelRatio||1); const ctx=c.getContext('2d');
                function resize(){ c.width=innerWidth*dpr; c.height=innerHeight*dpr; c.style.width=innerWidth+'px'; c.style.height=innerHeight+'px'; ctx.setTransform(dpr,0,0,dpr,0,0);} resize();
                const pieces=[]; for(let i=0;i<160;i++){ pieces.push({x:Math.random()*innerWidth,y:-20-Math.random()*200,vx:(Math.random()-0.5)*1.5,vy:Math.random()*2+1.5,rot:Math.random()*6.28,vr:(Math.random()-0.5)*0.2,color:`hsl(${(Math.random()*360)|0} 90% 60%)`}); }
                let end=Date.now()+ms;
                (function loop(){ ctx.clearRect(0,0,innerWidth,innerHeight); pieces.forEach(p=>{ p.x+=p.vx; p.y+=p.vy; p.vy+=0.02; p.rot+=p.vr; ctx.save(); ctx.translate(p.x,p.y); ctx.rotate(p.rot); ctx.fillStyle=p.color; ctx.fillRect(-3,-6,6,12); ctx.restore(); }); if(Date.now()<end){ requestAnimationFrame(loop);} else { c.remove(); } })();
            }

            btn.addEventListener('click', ()=>{
                partyMode(15);
                gravityStorm(6);
                fireworks(3500);
                confettiShake(2000);
            }, { passive:true });
        })();
        // Hanging cards animation (crooked, swaying as if nailed at top-left)
        (function(){
            const els = document.querySelectorAll('[data-fly-arena]');
            const cards = Array.from(els);
            if (!cards.length) return;
            const params = cards.map((el, idx)=>{
                el.classList.add('hang');
                // Set anchor (nail) position per card
                if (idx === 0) { // first: top-right
                    el.style.setProperty('--nail-x', 'calc(100% - 12px)');
                    el.style.setProperty('--nail-y', '12px');
                } else if (idx === 1) { // second: top-left
                    el.style.setProperty('--nail-x', '12px');
                    el.style.setProperty('--nail-y', '12px');
                } else { // third: top-center
                    el.style.setProperty('--nail-x', '50%');
                    el.style.setProperty('--nail-y', '10px');
                }
                return {
                    el,
                    base: (idx===0? -3 : idx===1? 3 : 0) + (Math.random()*2-1), // initial tilt
                    amp: idx===2 ? 2.8 : (1.5 + Math.random()*1.4),            // center one sways a bit more
                    speed: 0.6 + Math.random()*0.7,                              // rad/s
                    phase: Math.random()*Math.PI*2
                };
            });
            const start = performance.now();
            function loop(){
                const t = (performance.now() - start)/1000;
                for (const p of params){
                    const a = p.base + p.amp * Math.sin(t * p.speed + p.phase);
                    p.el.style.transform = `rotate(${a}deg)`;
                }
                requestAnimationFrame(loop);
            }
            requestAnimationFrame(loop);
        })();
        // Real galaxy photo background (fixed layer)
        (function(){
            if (!document.getElementById('galaxy-bg')){
                const d = document.createElement('div'); d.id='galaxy-bg'; document.body.prepend(d);
            }
        })();
    </script>
    <style>
        /* Flying emoji button inside its card */
        .fly-emoji-btn { position: absolute !important; z-index: 6; }
        [data-fly-arena].relative-host { position: relative; }
    </style>
    <script>
        (function(){
            // Find the exact button with 😈😈😈 text
            const btn = Array.from(document.querySelectorAll('button'))
                .find(b => (b.textContent||'').trim() === '😈😈😈');
            if (!btn) return;

            // Find its containing card (prefer data-fly-arena)
            const host = btn.closest('[data-fly-arena]') || btn.closest('.card') || btn.parentElement;
            if (!host) return;
            host.classList.add('relative-host');

            // Measure and place as absolute inside host
            const rect = host.getBoundingClientRect();
            const style = getComputedStyle(host);
            const padL = parseFloat(style.paddingLeft)||0, padT=parseFloat(style.paddingTop)||0;

            btn.classList.add('fly-emoji-btn');

            // Initial position within card
            let bx = 24, by = 24; // will be clamped after measuring size
            let bw = btn.offsetWidth || 70, bh = btn.offsetHeight || 28;
            function place(){ btn.style.left = bx + 'px'; btn.style.top = by + 'px'; }
            place();

            // Simple “fly” motion like other flies
            let vx = (Math.random()*2-1)*0.9, vy = (Math.random()*2-1)*0.9;
            function step(){
                const r = host.getBoundingClientRect();
                bw = btn.offsetWidth || bw; bh = btn.offsetHeight || bh;
                const W = r.width, H = r.height;

                // Wander with light noise
                const noise = 0.08;
                vx += (Math.random()-0.5)*noise; vy += (Math.random()-0.5)*noise;
                const max = 1.6; const sp = Math.hypot(vx,vy)||1; if (sp>max){ vx = vx/sp*max; vy = vy/sp*max; }
                bx += vx; by += vy;

                // Bounce from edges inside host
                const minX = 6, minY = 6, maxX = Math.max(6, W - bw - 6), maxY = Math.max(6, H - bh - 6);
                if (bx < minX){ bx = minX; vx = Math.abs(vx); }
                if (bx > maxX){ bx = maxX; vx = -Math.abs(vx); }
                if (by < minY){ by = minY; vy = Math.abs(vy); }
                if (by > maxY){ by = maxY; vy = -Math.abs(vy); }

                place();
                requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        })();
    </script>
    <style>
        /* Escaping STOP button */
        .escape-btn {
            position: fixed !important; z-index: 2147483647; pointer-events: auto; left: 0; top: 0;
            padding: 10px 18px; font-size: 16px; line-height: 1.2;
            border-radius: 10px; box-shadow: 0 10px 24px rgba(0,0,0,0.28);
        }
        /* Endless quake + red edges */
        @keyframes quake-shake {
            0% { transform: translate3d(0,0,0) rotate(0deg); }
            20% { transform: translate3d(-3px,2px,0) rotate(-0.4deg); }
            40% { transform: translate3d(3px,-2px,0) rotate(0.4deg); }
            60% { transform: translate3d(-2px,3px,0) rotate(-0.6deg); }
            80% { transform: translate3d(2px,-3px,0) rotate(0.6deg); }
            100% { transform: translate3d(0,0,0) rotate(0deg); }
        }
        body.quake { animation: quake-shake 120ms linear infinite; }
        #edge-flash { position: fixed; inset: 0; z-index: 2147483646; pointer-events: none; }
        #edge-flash .edge { position: absolute; opacity: .08; filter: saturate(120%); box-shadow: 0 0 0 rgba(0,0,0,0); }
        /* Thickness of edges */
        #edge-flash .top, #edge-flash .bottom { height: 12px; width: 100%; left: 0; }
        #edge-flash .left, #edge-flash .right { width: 12px; height: 100%; top: 0; }
        #edge-flash .top    { top:0;  background: linear-gradient(to bottom, rgba(255,60,72,0.0), rgba(255,60,72,0.95), rgba(255,60,72,0.0)); }
        #edge-flash .bottom { bottom:0; background: linear-gradient(to top,    rgba(255,60,72,0.0), rgba(255,60,72,0.95), rgba(255,60,72,0.0)); }
        #edge-flash .left   { left:0;  background: linear-gradient(to right,   rgba(255,60,72,0.0), rgba(255,60,72,0.95), rgba(255,60,72,0.0)); }
        #edge-flash .right  { right:0; background: linear-gradient(to left,    rgba(255,60,72,0.0), rgba(255,60,72,0.95), rgba(255,60,72,0.0)); }
        @keyframes edge-neon {
            0%   { opacity: .10; box-shadow: 0 0 10px rgba(255,60,72,.35), 0 0 26px rgba(255,60,72,.20); filter: blur(0.2px) saturate(130%); }
            50%  { opacity: .95; box-shadow: 0 0 18px rgba(255,60,72,.85), 0 0 44px rgba(255,60,72,.55); filter: blur(0.4px) saturate(150%); }
            100% { opacity: .65; box-shadow: 0 0 14px rgba(255,60,72,.60), 0 0 34px rgba(255,60,72,.35); filter: blur(0.3px) saturate(140%); }
        }
        body.quake #edge-flash .top    { animation: edge-neon 900ms ease-in-out infinite alternate; }
        body.quake #edge-flash .right  { animation: edge-neon 900ms ease-in-out .1s infinite alternate; }
        body.quake #edge-flash .bottom { animation: edge-neon 900ms ease-in-out .2s infinite alternate; }
        body.quake #edge-flash .left   { animation: edge-neon 900ms ease-in-out .3s infinite alternate; }
    </style>
    <script>
        (function(){
            let btn = Array.from(document.querySelectorAll('button'))
                .find(b => /\bSTOP\b/i.test((b.textContent||'').trim()));
            if (!btn) {
                // Fallback: create a STOP button if not present
                btn = document.createElement('button');
                btn.textContent = 'STOP';
                btn.className = 'btn btn-sm escape-btn';
                btn.style.padding = '10px 18px';
                btn.style.fontSize = '16px';
                btn.style.background = '#ef4444';
                btn.style.color = '#fff';
                btn.style.borderRadius = '10px';
                btn.style.boxShadow = '0 10px 24px rgba(0,0,0,0.28)';
                document.body.appendChild(btn);
            }

            // MODE: Make this STOP button wander inside its own card (no cursor-escape)
            (function makeStopFlyInCard(){
                const host = btn.closest('[data-fly-arena]') || btn.closest('.card') || btn.parentElement;
                if (!host) return;
                host.classList.add('relative-host');
                btn.classList.remove('escape-btn');
                btn.classList.add('fly-emoji-btn');
                // Place relative within host
                let bx = 24, by = 24;
                let bw = btn.offsetWidth || 90, bh = btn.offsetHeight || 32;
                function placeRel(){ btn.style.left = bx + 'px'; btn.style.top = by + 'px'; }
                placeRel();
                // Wander motion (2x faster)
                let vx = (Math.random()*2-1)*2.0, vy = (Math.random()*2-1)*2.0;
                function step(){
                    const r = host.getBoundingClientRect();
                    bw = btn.offsetWidth || bw; bh = btn.offsetHeight || bh;
                    const W = r.width, H = r.height;
                    const noise = 0.12; vx += (Math.random()-0.5)*noise; vy += (Math.random()-0.5)*noise;
                    const max = 3.6; const sp = Math.hypot(vx,vy)||1; if (sp>max){ vx = vx/sp*max; vy = vy/sp*max; }
                    bx += vx; by += vy;
                    const minX = 6, minY = 6, maxX = Math.max(6, W - bw - 6), maxY = Math.max(6, H - bh - 6);
                    if (bx < minX){ bx = minX; vx = Math.abs(vx); }
                    if (bx > maxX){ bx = maxX; vx = -Math.abs(vx); }
                    if (by < minY){ by = minY; vy = Math.abs(vy); }
                    if (by > maxY){ by = maxY; vy = -Math.abs(vy); }
                    placeRel();
                    requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
                // Endless quake on click
                btn.addEventListener('click', ()=>{
                    if (!document.body.classList.contains('quake')){
                        document.body.classList.add('quake');
                        if (!document.getElementById('edge-flash')){
                            const ef = document.createElement('div'); ef.id='edge-flash';
                            ['top','right','bottom','left'].forEach(cls=>{ const d=document.createElement('div'); d.className='edge '+cls; ef.appendChild(d); });
                            document.body.appendChild(ef);
                        }
                    }
                });
            })();
            // Stop here; do not run the old escape logic below
            return;

            // Allowed area is only inside the three main cards (not the galaxy background)
            const arenas = Array.from(document.querySelectorAll('[data-fly-arena]'));
            function getRects(){ return arenas.map(el=>el.getBoundingClientRect()); }
            function clampToRect(x,y,w,h,r){
                return {
                    x: Math.max(r.left+2, Math.min(r.right - w - 2, x)),
                    y: Math.max(r.top+2, Math.min(r.bottom - h - 2, y)),
                };
            }
            // Lift to body and make fixed
            const rect = btn.getBoundingClientRect();
            let w = rect.width || 90, h = rect.height || 32;
            btn.classList.add('escape-btn');
            if (!document.body.contains(btn)) document.body.appendChild(btn);
            const rs0 = getRects();
            const r0 = rs0[0] || {left:10, top:10, right: innerWidth-10, bottom: innerHeight-10};
            let x = (r0.left + r0.right)/2 - w/2;
            let y = (r0.top + r0.bottom)/2 - h/2;
            ({x,y} = clampToRect(x,y,w,h,r0));

            function clamp(v, a, b){ return Math.max(a, Math.min(b, v)); }
            function place(){ btn.style.left = `${x}px`; btn.style.top = `${y}px`; }
            place();

            let mx = innerWidth/2, my = innerHeight/2;
            addEventListener('mousemove', e=>{ mx = e.clientX; my = e.clientY; /* immediate guard */ if (btn) instantCheck(); });

            function farthestInside(rects, px, py){
                let bestX = x, bestY = y, bestD = -1;
                for (const r of rects){
                    const margin = 8;
                    const pts = [
                        {x: r.left+margin, y: r.top+margin},
                        {x: r.right - w - margin, y: r.top+margin},
                        {x: r.left+margin, y: r.bottom - h - margin},
                        {x: r.right - w - margin, y: r.bottom - h - margin},
                        {x: (r.left+r.right)/2 - w/2, y: r.top+margin},
                        {x: (r.left+r.right)/2 - w/2, y: r.bottom - h - margin},
                        {x: r.left+margin, y: (r.top+r.bottom)/2 - h/2},
                        {x: r.right - w - margin, y: (r.top+r.bottom)/2 - h/2}
                    ];
                    for (const p of pts){
                        const cxp = Math.max(r.left+2, Math.min(r.right - w - 2, p.x));
                        const cyp = Math.max(r.top+2, Math.min(r.bottom - h - 2, p.y));
                        const d2 = (cxp - px)*(cxp - px) + (cyp - py)*(cyp - py);
                        if (d2 > bestD){ bestD = d2; bestX = cxp; bestY = cyp; }
                    }
                }
                return {x: bestX, y: bestY};
            }

            function instantCheck(){
                const rects = getRects();
                const cx = x + w/2, cy = y + h/2;
                const dx = cx - mx, dy = cy - my; const dist = Math.hypot(dx,dy)||1;
                const threshold = 76;
                if (dist < threshold){ const fp = farthestInside(rects, mx, my); x = fp.x; y = fp.y; place(); }
            }

            function flee(){
                // Update current button size (in case of responsive CSS)
                w = btn.offsetWidth || w; h = btn.offsetHeight || h;
                const cx = x + w/2, cy = y + h/2;
                const dx = cx - mx, dy = cy - my;
                const dist = Math.hypot(dx, dy) || 1;
                const rects = getRects();
                const threshold = 76; // ~2cm in px
                if (dist < threshold){
                    const fp = farthestInside(rects, mx, my); x = fp.x; y = fp.y;
                }
                // If cornered, teleport
                if (dist < 30){
                    // Teleport into the nearest card relative to cursor
                    let ni = 0, best = Infinity;
                    rects.forEach((r,i)=>{ const cxr=(r.left+r.right)/2, cyr=(r.top+r.bottom)/2; const d=(mx-cxr)*(mx-cxr)+(my-cyr)*(my-cyr); if(d<best){best=d; ni=i;} });
                    const rr = rects[ni];
                    x = rr.left + 30 + Math.random()*Math.max(40, (rr.right - rr.left) - w - 60);
                    y = rr.top + 40 + Math.random()*Math.max(60, (rr.bottom - rr.top) - h - 80);
                }
                // Keep inside a card: if outside all, clamp into nearest card
                let inside = false;
                for (const r of rects){ if (x>=r.left+2 && x<=r.right-w-2 && y>=r.top+2 && y<=r.bottom-h-2){ inside = true; break; } }
                if (!inside){
                    let ni = 0, best = Infinity;
                    rects.forEach((r,i)=>{ const cxr=(r.left+r.right)/2, cyr=(r.top+r.bottom)/2; const d=(x-cxr)*(x-cxr)+(y-cyr)*(y-cyr); if(d<best){best=d; ni=i;} });
                    const rr = rects[ni];
                    ({x,y} = clampToRect(x,y,w,h,rr));
                }
                place();
                requestAnimationFrame(flee);
            }
            requestAnimationFrame(flee);

            function startQuake(){
                if (!document.body.classList.contains('quake')){
                    document.body.classList.add('quake');
                    if (!document.getElementById('edge-flash')){
                        const ef = document.createElement('div'); ef.id='edge-flash';
                        ['top','right','bottom','left'].forEach(cls=>{ const d=document.createElement('div'); d.className='edge '+cls; ef.appendChild(d); });
                        document.body.appendChild(ef);
                    }
                }
            }
            // Trigger quake on click or mousedown (and still block default)
            btn.addEventListener('mousedown', e=>{ e.preventDefault(); e.stopPropagation(); startQuake(); });
            btn.addEventListener('click', e=>{ e.preventDefault(); e.stopPropagation(); startQuake(); });
            btn.addEventListener('focus', ()=>{ const rects=getRects(); let ni=0,best=Infinity; rects.forEach((r,i)=>{ const cxr=(r.left+r.right)/2, cyr=(r.top+r.bottom)/2; const d=(mx-cxr)*(mx-cxr)+(my-cyr)*(my-cyr); if(d<best){best=d; ni=i;} }); const rr=rects[ni]; x = rr.left + 30 + Math.random()*Math.max(40,(rr.right-rr.left)-w-60); y = rr.top + 40 + Math.random()*Math.max(60,(rr.bottom-rr.top)-h-80); place(); });
            addEventListener('resize', ()=>{ const rects=getRects(); w = btn.offsetWidth||w; h = btn.offsetHeight||h; let ni=0,best=Infinity; rects.forEach((r,i)=>{ const cxr=(r.left+r.right)/2, cyr=(r.top+r.bottom)/2; const d=(x-cxr)*(x-cxr)+(y-cyr)*(y-cyr); if(d<best){best=d; ni=i;} }); const rr=rects[ni]||{left:10,top:10,right:innerWidth-10,bottom:innerHeight-10}; ({x,y}=clampToRect(x,y,w,h,rr)); place(); });
        })();
    </script>
<!-- yakunidan 6 -->
@endsection
