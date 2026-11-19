/*-----------------------------
* BuddyPress Birthdays: today's-birthday celebration.
* Everything else on the list is server-rendered.
-----------------------------*/

(function($) {
    "use strict";

    var BPBirthdays = {
        init: function() {
            var $today = $('.widget_bp_birthdays .today-birthday');

            if (!$today.length) {
                return;
            }

            // The pulse is plain CSS; reduced motion switches it off there.
            $today.each(function(index) {
                var $item = $(this);
                setTimeout(function() {
                    $item.addClass('birthday-celebrate');
                }, index * 200);
            });

            // Confetti is the Display tab's switch, passed in by core-init.php.
            if (typeof bbBirthdays !== 'undefined' && bbBirthdays.confetti) {
                $today.each(function(index, element) {
                    setTimeout(function() {
                        BPBirthdays.createConfetti($(element));
                    }, index * 500);
                });
            }
        },

        createConfetti: function( $target, options = {} ) {
            // Professional-grade confetti with realistic physics and premium aesthetics
            try {
                // Check for reduced motion preference
                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                const colors = [
                    { front: '#ff6b9d', back: '#c44569' },
                    { front: '#4facfe', back: '#00f2fe' },
                    { front: '#43e97b', back: '#38f9d7' },
                    { front: '#f093fb', back: '#f5576c' },
                    { front: '#ffd700', back: '#ffed4e' },
                    { front: '#fa709a', back: '#fee140' },
                    { front: '#30cfd0', back: '#330867' },
                    { front: '#a8edea', back: '#fed6e3' },
                    { front: '#ff9a56', back: '#ff6a88' },
                    { front: '#667eea', back: '#764ba2' }
                ];

                const shapes = ['circle', 'square', 'ribbon', 'triangle'];
                const count = options.count || 50;
                
                const rect = $target[0].getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;

                const container = document.createElement('div');
                container.className = 'bb-confetti-container';
                container.setAttribute('aria-hidden', 'true');
                container.style.perspective = '1000px';
                document.body.appendChild(container);

                // Create confetti in waves for layered effect
                for (let i = 0; i < count; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'bb-confetti bb-confetti-particle';
                    
                    const shape = shapes[Math.floor(Math.random() * shapes.length)];
                    const colorPair = colors[Math.floor(Math.random() * colors.length)];
                    const size = shape === 'ribbon' ? 
                        { w: 4 + Math.random() * 4, h: 12 + Math.random() * 8 } :
                        { w: 8 + Math.random() * 6, h: 8 + Math.random() * 6 };
                    
                    // Explosion vector - wider spread with cone pattern
                    const angle = (Math.random() * 140 - 70) * (Math.PI / 180); // -70° to +70°
                    const velocity = 150 + Math.random() * 200;
                    const spread = Math.cos(angle) * velocity;
                    const lift = -Math.abs(Math.sin(angle) * velocity) - 50;
                    
                    // Physics properties
                    const gravity = 800 + Math.random() * 400;
                    const drag = 0.98 - Math.random() * 0.03;
                    const rotationX = (Math.random() - 0.5) * 720;
                    const rotationY = (Math.random() - 0.5) * 720;
                    const rotationZ = (Math.random() - 0.5) * 1080;
                    const wobble = (Math.random() - 0.5) * 120;
                    
                    const delay = Math.random() * 0.15;
                    const duration = 2.5 + Math.random() * 1;

                    // Styling
                    particle.style.cssText = `
                        position: absolute;
                        left: ${centerX}px;
                        top: ${centerY + window.scrollY}px;
                        width: ${size.w}px;
                        height: ${size.h}px;
                        background: linear-gradient(135deg, ${colorPair.front} 0%, ${colorPair.back} 100%);
                        pointer-events: none;
                        will-change: transform, opacity;
                        transform-style: preserve-3d;
                        z-index: 9999;
                        box-shadow: 0 0 4px rgba(0,0,0,0.1);
                    `;

                    if (shape === 'circle') {
                        particle.style.borderRadius = '50%';
                    } else if (shape === 'ribbon') {
                        particle.style.borderRadius = '2px';
                    } else if (shape === 'triangle') {
                        particle.style.background = 'transparent';
                        particle.style.borderLeft = `${size.w/2}px solid transparent`;
                        particle.style.borderRight = `${size.w/2}px solid transparent`;
                        particle.style.borderBottom = `${size.h}px solid ${colorPair.front}`;
                        particle.style.width = '0';
                        particle.style.height = '0';
                    } else {
                        particle.style.borderRadius = '1px';
                    }

                    container.appendChild(particle);

                    // Animate with realistic physics
                    (function(p, spreadX, liftY, grav, dr, rotX, rotY, rotZ, wob, dur, del) {
                        setTimeout(function() {
                            const time = dur * 1000;
                            const frames = [];
                            const steps = 60;
                            
                            let posX = 0, posY = 0, velX = spreadX, velY = liftY;
                            
                            for (let step = 0; step <= steps; step++) {
                                const t = step / steps;
                                const dt = dur / steps;
                                
                                // Apply physics
                                velY += grav * dt;
                                velX *= dr;
                                velY *= dr;
                                posX += velX * dt;
                                posY += velY * dt;
                                
                                const wobbleX = Math.sin(t * Math.PI * 4) * wob * t;
                                const opacity = Math.max(0, 1 - Math.pow(t, 2));
                                
                                frames.push({
                                    transform: `
                                        translate3d(${posX + wobbleX}px, ${posY}px, 0)
                                        rotateX(${rotX * t}deg)
                                        rotateY(${rotY * t}deg)
                                        rotateZ(${rotZ * t}deg)
                                    `,
                                    opacity: opacity,
                                    offset: t
                                });
                            }

                            const animation = p.animate(frames, {
                                duration: time,
                                easing: 'linear',
                                fill: 'forwards'
                            });

                            animation.onfinish = function() {
                                if (p && p.parentNode) p.parentNode.removeChild(p);
                            };
                        }, del * 1000);
                    })(particle, spread, lift, gravity, drag, rotationX, rotationY, rotationZ, wobble, duration, delay);
                }

                // Cleanup container
                setTimeout(function() {
                    if (container && container.parentNode) container.parentNode.removeChild(container);
                }, 5000);
            } catch (e) {
                // Fail silently
            }
        }
    };

    $(function() {
        BPBirthdays.init();
    });

})(jQuery);
