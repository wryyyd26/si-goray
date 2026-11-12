
import * as THREE from 'three';

import { OrbitControls } from
    'three/addons/controls/OrbitControls.js';

import { GLTFLoader } from
    'three/addons/loaders/GLTFLoader.js';

import { ARButton } from
    'three/addons/webxr/ARButton.js';

const container = document.getElementById('gor-3d-viewer');
const loading = document.getElementById('gor-ar-loading');
const status = document.getElementById('gor-ar-status');
const resetButton = document.getElementById('gor-reset-view');
const arContainer = document.getElementById('gor-ar-button-container');

const modelUrl = container.dataset.modelUrl;

// SCENE
const scene = new THREE.Scene();
scene.background = new THREE.Color('#e9f3fb');

// CAMERA
const camera = new THREE.PerspectiveCamera(
    45,
    1,
    0.01,
    1000
);

camera.position.set(4, 3, 5);

// RENDERER
const renderer = new THREE.WebGLRenderer({
    antialias: true,
    alpha: true
});

renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.xr.enabled = true;

container.appendChild(renderer.domElement);

// LIGHTS
scene.add(new THREE.HemisphereLight(0xffffff, 0x9bb6ce, 2));

const sunlight = new THREE.DirectionalLight(0xffffff, 2);
sunlight.position.set(5, 10, 7);
scene.add(sunlight);

// CONTROLS
const controls = new OrbitControls(camera, renderer.domElement);

controls.enableDamping = true;
controls.dampingFactor = 0.06;
controls.minDistance = 1;
controls.maxDistance = 50;

const defaultCamera = new THREE.Vector3(4, 3, 5);

// GROUND FOR 3D VIEWER
const grid = new THREE.GridHelper(10, 20, 0xc4d5e5, 0xdce8f2);
grid.position.y = -0.02;
scene.add(grid);

// MODEL GROUP
const modelGroup = new THREE.Group();
scene.add(modelGroup);

let modelLoaded = false;

// LOAD GLB
const loader = new GLTFLoader();

loader.load(
    modelUrl,

    function (gltf) {
        const model = gltf.scene;

        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        const maxDimension = Math.max(
            size.x,
            size.y,
            size.z
        );

        if (!Number.isFinite(maxDimension) || maxDimension <= 0) {
            loading.textContent = 'Model GLB tidak memiliki ukuran yang valid.';
            return;
        }

        // NORMALISASI UKURAN MODEL
        const scale = 2.5 / maxDimension;
        model.scale.setScalar(scale);

        // PUSATKAN MODEL DAN LETAKKAN DASARNYA DI Y=0
        model.position.set(
            -center.x * scale,
            -box.min.y * scale,
            -center.z * scale
        );

        modelGroup.add(model);

        modelLoaded = true;
        loading.style.display = 'none';

        status.textContent =
            'Model berhasil dimuat. Geser untuk memutar atau zoom untuk memperbesar.';
    },

    undefined,

    function (error) {
        console.error('Gagal memuat GLB:', error);

        loading.textContent =
            'Model 3D belum dapat dimuat. Periksa file denah-gor.glb.';
    }
);

// RESET VIEW
resetButton.addEventListener('click', function () {
    if (renderer.xr.isPresenting) return;

    camera.position.copy(defaultCamera);
    controls.target.set(0, 0.6, 0);
    controls.update();
});

controls.target.set(0, 0.6, 0);
controls.update();

// RESPONSIVE CANVAS
function resizeViewer() {
    const width = container.clientWidth;
    const height = container.clientHeight;

    if (!width || !height) return;

    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, false);
}

const resizeObserver = new ResizeObserver(resizeViewer);
resizeObserver.observe(container);
resizeViewer();

// AR HIT TEST
const reticle = new THREE.Mesh(
    new THREE.RingGeometry(0.12, 0.16, 32),
    new THREE.MeshBasicMaterial({
        color: 0x22c55e,
        side: THREE.DoubleSide
    })
);

reticle.rotation.x = -Math.PI / 2;
reticle.matrixAutoUpdate = false;
reticle.visible = false;
scene.add(reticle);

let hitTestSource = null;
let hitTestRequested = false;

// AR PLACEMENT
const controller = renderer.xr.getController(0);

controller.addEventListener('select', function () {
    if (!reticle.visible || !modelLoaded) return;

    // Pindahkan model ke permukaan yang terdeteksi.
    modelGroup.position.setFromMatrixPosition(reticle.matrix);

    // Model miniatur untuk meja/lantai.
    modelGroup.scale.setScalar(0.35);
    modelGroup.visible = true;

    status.textContent =
        'Model ditempatkan. Ketuk permukaan lain untuk memindahkannya.';
});

scene.add(controller);

// AR SESSION
renderer.xr.addEventListener('sessionstart', function () {
    scene.background = null;
    grid.visible = false;
    modelGroup.visible = false;
    controls.enabled = false;

    hitTestRequested = false;
    hitTestSource = null;

    status.textContent =
        'Arahkan kamera ke permukaan datar, lalu ketuk untuk menempatkan model.';
});

renderer.xr.addEventListener('sessionend', function () {
    scene.background = new THREE.Color('#e9f3fb');

    grid.visible = true;
    modelGroup.visible = true;
    modelGroup.position.set(0, 0, 0);
    modelGroup.scale.setScalar(1);

    controls.enabled = true;

    hitTestSource?.cancel();
    hitTestSource = null;
    hitTestRequested = false;
    reticle.visible = false;

    status.textContent =
        'Mode 3D aktif. Geser untuk memutar model.';
});

// CREATE AR BUTTON
if (navigator.xr && window.isSecureContext) {
    const arButton = ARButton.createButton(renderer, {
        requiredFeatures: ['hit-test'],
        optionalFeatures: ['dom-overlay'],
        domOverlay: { root: document.body }
    });

    arContainer.appendChild(arButton);
} else {
    const unavailable = document.createElement('p');
    unavailable.className = 'gor-ar-unavailable';
    unavailable.textContent =
        'AR tidak tersedia pada browser atau koneksi ini.';
    arContainer.appendChild(unavailable);
}

// ANIMATION LOOP
renderer.setAnimationLoop(function (_time, frame) {
    if (renderer.xr.isPresenting && frame) {
        const session = renderer.xr.getSession();

        if (!hitTestRequested) {
            hitTestRequested = true;

            session.requestReferenceSpace('viewer')
                .then((viewerSpace) => {
                    return session.requestHitTestSource({
                        space: viewerSpace
                    });
                })
                .then((source) => {
                    hitTestSource = source;
                })
                .catch((error) => {
                    console.error('AR hit test gagal:', error);
                    hitTestRequested = false;
                });
        }

        if (hitTestSource) {
            const referenceSpace = renderer.xr.getReferenceSpace();

            if (referenceSpace) {
                const hits = frame.getHitTestResults(hitTestSource);

                if (hits.length > 0) {
                    const pose = hits[0].getPose(referenceSpace);

                    if (pose) {
                        reticle.visible = true;
                        reticle.matrix.fromArray(
                            pose.transform.matrix
                        );
                    }
                } else {
                    reticle.visible = false;
                }
            }
        }
    }

    if (!renderer.xr.isPresenting) {
        controls.update();
    }

    renderer.render(scene, camera);
});
