document.addEventListener('DOMContentLoaded', function () {
    // Observer para animações de scroll
    const animatedSections = document.querySelectorAll('.animated-section');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, { threshold: 0.1 });
    animatedSections.forEach(section => observer.observe(section));

    // Lógica do "Inventário Levitante" com Three.js
    const canvas = document.getElementById('hero-canvas');
    if (canvas && typeof THREE !== 'undefined') {
        let scene, camera, renderer, raycaster, mouse;
        const objects = [];
        let intersectedObject = null;

        // --- Setup ---
        function init() {
            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(75, canvas.offsetWidth / canvas.offsetHeight, 0.1, 1000);
            renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
            renderer.setClearColor(0x111111, 1);
            
            raycaster = new THREE.Raycaster();
            mouse = new THREE.Vector2();

            window.addEventListener('resize', onWindowResize);
            onWindowResize();

            // --- Iluminação ---
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
            scene.add(ambientLight);
            const spotlight = new THREE.SpotLight(0xffffff, 0.8, 30, Math.PI / 4, 0.5, 2);
            spotlight.position.set(0, 15, 20);
            scene.add(spotlight);

            // --- Objetos ---
            const boxGeometry = new THREE.BoxGeometry(1, 1, 1);
            const boxMaterial = new THREE.MeshPhysicalMaterial({
                color: 0xffffff,
                transmission: 0.9,
                roughness: 0.2,
                transparent: true,
                opacity: 0.8
            });

            const count = 5;
            for (let x = -count; x <= count; x++) {
                for (let y = -count; y <= count; y++) {
                    for (let z = -count; z <= count; z++) {
                        if (Math.random() > 0.9) { // Adiciona objetos de forma esparsa
                            const mesh = new THREE.Mesh(boxGeometry, boxMaterial.clone());
                            mesh.position.set(x * 2, y * 2, z * 2);
                            mesh.userData.initialY = y * 2;
                            mesh.userData.phase = Math.random() * Math.PI * 2;
                            scene.add(mesh);
                            objects.push(mesh);
                        }
                    }
                }
            }
            camera.position.z = 10;
            animate();
        }

        // --- Loop de Animação e Interação ---
        const clock = new THREE.Clock();
        function animate() {
            requestAnimationFrame(animate);
            const elapsedTime = clock.getElapsedTime();

            // Animação de "respiração"
            objects.forEach(obj => {
                obj.position.y = obj.userData.initialY + Math.sin(elapsedTime * 0.5 + obj.userData.phase) * 0.5;
            });
            
            // Raycasting para interação
            raycaster.setFromCamera(mouse, camera);
            const intersects = raycaster.intersectObjects(objects);

            if (intersects.length > 0) {
                if (intersectedObject !== intersects[0].object) {
                    // Restaura o anterior
                    if (intersectedObject) {
                        intersectedObject.material.emissive.setHex(0x000000);
                    }
                    // Destaca o novo
                    intersectedObject = intersects[0].object;
                    intersectedObject.material.emissive.setHex(0x00ffff); // Ciano para destaque
                }
            } else {
                // Limpa o destaque se não houver interseção
                if (intersectedObject) {
                    intersectedObject.material.emissive.setHex(0x000000);
                }
                intersectedObject = null;
            }

            // Rotação sutil da cena
            scene.rotation.y = elapsedTime * 0.05;

            renderer.render(scene, camera);
        }

        function onWindowResize() {
            const width = canvas.offsetWidth;
            const height = canvas.offsetHeight;
            renderer.setSize(width, height);
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
        }

        function onMouseMove(event) {
            const rect = canvas.getBoundingClientRect();
            mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
        }

        window.addEventListener('mousemove', onMouseMove);
        init();
    }
});
