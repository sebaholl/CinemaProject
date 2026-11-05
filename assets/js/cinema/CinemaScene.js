import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { gsap } from 'gsap';

const COLORS = {
  available: 0x16295c,
  occupied: 0x7a2520,
  selected: 0xf2bd48,
  hover: 0xc6cedd,
};

const SCREEN = {
  width: 12,
  height: 5.25,
  x: 0,
  y: 4.55,
  z: -11.7,
  mobileScaleX: 0.8,
  mobileScaleY: 0.92,
};

export class CinemaScene {
  constructor(container, seats, callbacks = {}) {
    this.container = container;
    this.seats = seats;
    this.callbacks = callbacks;
    this.selectedSeat = null;
    this.selectedSeats = new Map();
    this.maxSeats = callbacks.maxSeats ?? 2;
    this.hoveredSeat = null;
    this.seatObjects = new Map();
    this.pointerStart = new THREE.Vector2();
    this.activePointers = new Set();
    this.pointerPositions = new Map();
    this.lastPinchDistance = null;
    this.hadMultiTouch = false;
    this.isSeatView = false;
    this.isMobile = this.getIsMobile();
    this.isPortrait = window.innerHeight >= window.innerWidth;
    this.viewportMode = this.getViewportMode();
    this.hasMobileInteracted = false;

    this.scene = new THREE.Scene();
    this.scene.background = new THREE.Color(0x080a0e);
    this.scene.fog = new THREE.FogExp2(0x080a0e, 0.018);

    this.camera = new THREE.PerspectiveCamera(this.getOverviewFov(), 1, 0.1, 100);
    this.overviewPosition = new THREE.Vector3();
    this.overviewTarget = new THREE.Vector3();
    this.updateOverviewForDevice();
    this.camera.position.copy(this.overviewPosition);

    this.renderer = new THREE.WebGLRenderer({
      antialias: true,
      powerPreference: 'high-performance',
    });
    this.renderer.setPixelRatio(this.getRenderPixelRatio());
    this.renderer.setSize(container.clientWidth, container.clientHeight);
    this.renderer.shadowMap.enabled = !this.isMobile;
    this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    this.renderer.outputColorSpace = THREE.SRGBColorSpace;
    this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
    this.renderer.toneMappingExposure = this.isMobile ? 1.28 : 1.05;
    container.appendChild(this.renderer.domElement);

    this.controls = new OrbitControls(this.camera, this.renderer.domElement);
    this.controls.enableDamping = true;
    this.controls.dampingFactor = this.isMobile ? 0.1 : 0.06;
    this.controls.enablePan = !this.isMobile;
    this.controls.touches.ONE = THREE.TOUCH.ROTATE;
    this.controls.touches.TWO = THREE.TOUCH.DOLLY_ROTATE;
    this.controls.target.copy(this.overviewTarget);
    this.updateControlLimits();
    this.mobileOrbit = {
      theta: 0,
      phi: Math.PI / 3,
      radius: 22,
    };

    this.raycaster = new THREE.Raycaster();
    this.pointer = new THREE.Vector2();
    this.pickable = [];

    this.buildRoom();
    this.buildSeats();
    this.addLighting();
    this.bindEvents();
    this.resize();
    this.applyOverviewImmediately();
    this.animate();
  }

  getIsMobile() {
    return window.matchMedia('(pointer: coarse)').matches || window.innerWidth <= 768;
  }

  getViewportMode() {
    if (!this.isMobile) return 'desktop';
    return this.isPortrait ? 'mobile-portrait' : 'mobile-landscape';
  }

  getOverviewFov() {
    if (!this.isMobile) return 44;
    return this.isPortrait ? 70 : 52;
  }

  getRenderPixelRatio(isSeatView = false) {
    const deviceRatio = window.devicePixelRatio || 1;
    const maximum = this.isMobile ? (isSeatView ? 3 : 2.5) : 2.5;
    return Math.min(deviceRatio, maximum);
  }

  updateOverviewForDevice() {
    if (this.isMobile) {
      // Keep the same centre-line composition as desktop. The narrower mobile
      // canvas is handled with FOV instead of moving the camera off axis.
      this.overviewPosition.set(0, 12.8, 19.5);
      this.overviewTarget.set(0, 1.7, 0.2);
      return;
    }
    this.overviewPosition.set(0, 12.8, 19.5);
    this.overviewTarget.set(0, 1.7, 0.2);
  }

  updateControlLimits() {
    this.controls.enablePan = !this.isMobile;
    this.controls.rotateSpeed = this.isMobile ? 0.38 : 1;
    this.controls.zoomSpeed = this.isMobile ? 0.62 : 1;
    this.controls.dampingFactor = this.isMobile ? 0.1 : 0.06;
    this.controls.minDistance = this.isMobile ? 20 : 9;
    this.controls.maxDistance = this.isMobile ? 38 : 31;
    this.controls.minPolarAngle = this.isMobile ? Math.PI * 0.26 : 0;
    this.controls.maxPolarAngle = this.isMobile ? Math.PI * 0.41 : Math.PI * 0.47;
    this.controls.minAzimuthAngle = this.isMobile ? -Math.PI * 0.16 : -Infinity;
    this.controls.maxAzimuthAngle = this.isMobile ? Math.PI * 0.16 : Infinity;
  }

  applyOverviewImmediately() {
    gsap.killTweensOf(this.camera.position);
    gsap.killTweensOf(this.camera);
    this.camera.position.copy(this.overviewPosition);
    this.camera.up.set(0, 1, 0);
    this.camera.fov = this.getOverviewFov();
    this.camera.updateProjectionMatrix();
    this.controls.target.copy(this.overviewTarget);
    this.controls.enabled = !this.isMobile;
    this.hasMobileInteracted = false;
    this.resetMobileOrbit();
    if (!this.isMobile) this.controls.update();
    this.camera.lookAt(this.overviewTarget);
    if (!this.isMobile) this.controls.saveState();
  }

  resetMobileOrbit() {
    const offset = this.overviewPosition.clone().sub(this.overviewTarget);
    const spherical = new THREE.Spherical().setFromVector3(offset);
    this.mobileOrbit.theta = 0;
    this.mobileOrbit.phi = spherical.phi;
    this.mobileOrbit.radius = spherical.radius;
  }

  applyMobileOrbit() {
    if (!this.isMobile || this.isSeatView) return;
    const spherical = new THREE.Spherical(
      this.mobileOrbit.radius,
      this.mobileOrbit.phi,
      this.mobileOrbit.theta,
    );
    const offset = new THREE.Vector3().setFromSpherical(spherical);
    this.camera.position.copy(this.overviewTarget).add(offset);
    this.camera.up.set(0, 1, 0);
    this.camera.lookAt(this.overviewTarget);
  }

  updateMobileGesture(event) {
    if (!this.isMobile || this.isSeatView || !this.pointerPositions.has(event.pointerId)) return;

    const previous = this.pointerPositions.get(event.pointerId);
    const current = new THREE.Vector2(event.clientX, event.clientY);
    this.pointerPositions.set(event.pointerId, current);

    if (this.pointerPositions.size === 1) {
      if (this.hadMultiTouch) return;
      const deltaX = current.x - previous.x;
      const deltaY = current.y - previous.y;
      this.mobileOrbit.theta = THREE.MathUtils.clamp(
        this.mobileOrbit.theta - deltaX * 0.0032,
        -Math.PI * 0.17,
        Math.PI * 0.17,
      );
      this.mobileOrbit.phi = THREE.MathUtils.clamp(
        this.mobileOrbit.phi - deltaY * 0.0024,
        Math.PI * 0.27,
        Math.PI * 0.4,
      );
      this.applyMobileOrbit();
      return;
    }

    const points = [...this.pointerPositions.values()];
    const distance = points[0].distanceTo(points[1]);
    if (this.lastPinchDistance && distance > 0) {
      this.mobileOrbit.radius = THREE.MathUtils.clamp(
        this.mobileOrbit.radius * (this.lastPinchDistance / distance),
        18,
        31,
      );
      this.applyMobileOrbit();
    }
    this.lastPinchDistance = distance;
  }

  getScreenScaleX() {
    return this.isMobile ? SCREEN.mobileScaleX : 1;
  }

  updateScreenForDevice() {
    if (!this.screenMesh || !this.screenFrame) return;
    const scaleX = this.getScreenScaleX();
    const scaleY = this.isMobile ? SCREEN.mobileScaleY : 1;
    this.screenMesh.scale.set(scaleX, scaleY, 1);
    this.screenFrame.scale.set(scaleX, scaleY, 1);
    this.screenMaterial.map = this.isMobile
      ? this.screenTextures.mobile
      : this.screenTextures.desktop;
    this.screenMaterial.needsUpdate = true;
  }

  buildRoom() {
    const floor = new THREE.Mesh(
      new THREE.PlaneGeometry(24, 31),
      new THREE.MeshStandardMaterial({ color: 0x11141b, roughness: 0.92 }),
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.set(0, -0.03, 1);
    floor.receiveShadow = true;
    this.scene.add(floor);

    const backWall = new THREE.Mesh(
      new THREE.PlaneGeometry(24, 10),
      new THREE.MeshStandardMaterial({ color: 0x111218, roughness: 1 }),
    );
    backWall.position.set(0, 5, -12.2);
    this.scene.add(backWall);

    const sideMaterial = new THREE.MeshStandardMaterial({ color: 0x0d0f14, roughness: 0.95 });
    [-11.6, 11.6].forEach((x) => {
      const wall = new THREE.Mesh(new THREE.PlaneGeometry(31, 10), sideMaterial);
      wall.position.set(x, 5, 1);
      wall.rotation.y = x < 0 ? Math.PI / 2 : -Math.PI / 2;
      this.scene.add(wall);
    });

    this.screenFrame = new THREE.Mesh(
      new THREE.BoxGeometry(12.8, 6.05, 0.3),
      new THREE.MeshStandardMaterial({ color: 0x050608, roughness: 0.65 }),
    );
    this.screenFrame.position.set(SCREEN.x, SCREEN.y, SCREEN.z - 0.18);
    this.scene.add(this.screenFrame);

    this.screenTextures = {
      desktop: this.createScreenTexture(false),
      mobile: this.createScreenTexture(true),
    };
    this.screenMaterial = new THREE.MeshBasicMaterial({
      map: this.isMobile ? this.screenTextures.mobile : this.screenTextures.desktop,
      toneMapped: false,
    });
    this.screenMesh = new THREE.Mesh(
      new THREE.PlaneGeometry(SCREEN.width, SCREEN.height),
      this.screenMaterial,
    );
    this.screenMesh.position.set(SCREEN.x, SCREEN.y, SCREEN.z);
    this.scene.add(this.screenMesh);
    this.updateScreenForDevice();

    const platformMaterial = new THREE.MeshStandardMaterial({ color: 0x171a21, roughness: 0.9 });
    const platformBase = -0.03;
    for (let row = 1; row < 8; row += 1) {
      // Each tier is a solid riser that fills down to the floor instead of a
      // thin slab floating above empty space.
      const top = row * 0.36;
      const height = top - platformBase;
      const platform = new THREE.Mesh(
        new THREE.BoxGeometry(19, height, 1.6),
        platformMaterial,
      );
      platform.position.set(0, platformBase + height / 2, -4.7 + row * 1.6);
      platform.receiveShadow = true;
      platform.castShadow = true;
      this.scene.add(platform);
    }

    [-9.8, 9.8].forEach((x) => {
      for (let z = -8; z <= 10; z += 3) {
        const lightStrip = new THREE.Mesh(
          new THREE.BoxGeometry(0.08, 0.06, 1.05),
          new THREE.MeshBasicMaterial({ color: 0xb98a32 }),
        );
        lightStrip.position.set(x, 0.08, z);
        this.scene.add(lightStrip);
      }
    });
  }

  createScreenTexture(isMobileTexture = false) {
    const canvas = document.createElement('canvas');
    canvas.width = 2048;
    canvas.height = 896;
    const context = canvas.getContext('2d');
    context.imageSmoothingEnabled = true;
    context.imageSmoothingQuality = 'high';
    const gradient = context.createLinearGradient(0, 0, canvas.width, canvas.height);
    gradient.addColorStop(0, '#111827');
    gradient.addColorStop(0.5, '#263a52');
    gradient.addColorStop(1, '#8b5438');
    context.fillStyle = gradient;
    context.fillRect(0, 0, canvas.width, canvas.height);

    context.fillStyle = 'rgba(6, 8, 13, 0.38)';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.textAlign = 'center';
    context.fillStyle = '#f4efe6';
    if (isMobileTexture) {
      context.font = '700 48px Inter, Arial, sans-serif';
      context.fillText('FRIDAY PREMIERE', 1024, 245);
      context.font = '800 138px Inter, Arial, sans-serif';
      context.fillText('BEYOND THE', 1024, 425);
      context.fillText('HORIZON', 1024, 575);
      context.font = '500 36px Inter, Arial, sans-serif';
      context.fillStyle = 'rgba(244, 239, 230, 0.82)';
      context.fillText('Your perfect seat awaits', 1024, 670);
    } else {
      context.font = '600 40px Inter, Arial, sans-serif';
      context.fillText('FRIDAY PREMIERE', 1024, 325);
      context.font = '800 126px Inter, Arial, sans-serif';
      context.fillText('BEYOND THE HORIZON', 1024, 470);
      context.font = '400 32px Inter, Arial, sans-serif';
      context.fillStyle = 'rgba(244, 239, 230, 0.78)';
      context.fillText('Experience the story from your perfect seat', 1024, 548);
    }

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.anisotropy = this.renderer.capabilities.getMaxAnisotropy();
    texture.generateMipmaps = false;
    texture.minFilter = THREE.LinearFilter;
    texture.magFilter = THREE.LinearFilter;
    texture.needsUpdate = true;
    return texture;
  }

  addLighting() {
    this.ambientLight = new THREE.HemisphereLight(
      0x9eb6d6,
      0x211811,
      this.isMobile ? 1.9 : 1.25,
    );
    this.scene.add(this.ambientLight);

    const screenGlow = new THREE.RectAreaLight(0xe6f0ff, 18, SCREEN.width, SCREEN.height);
    screenGlow.position.set(SCREEN.x, SCREEN.y, SCREEN.z + 0.5);
    screenGlow.lookAt(0, 2, 2);
    this.scene.add(screenGlow);

    const ceilingLight = new THREE.SpotLight(0xf6c675, 38, 28, Math.PI / 4, 0.8, 1.3);
    ceilingLight.position.set(0, 9, 7);
    ceilingLight.target.position.set(0, 0, 0);
    ceilingLight.castShadow = true;
    this.scene.add(ceilingLight, ceilingLight.target);
  }

  buildSeats() {
    const baseGeometry = new THREE.BoxGeometry(0.72, 0.22, 0.72, 2, 2, 2);
    const backGeometry = new THREE.BoxGeometry(0.76, 0.92, 0.2, 2, 3, 2);
    const armGeometry = new THREE.BoxGeometry(0.1, 0.42, 0.54);
    // Pedestal that rests the seat on the step so it no longer looks like it is
    // floating. It spans from the step surface (local y = 0.1) up to the
    // underside of the cushion.
    const stepTopLocal = 0.1;
    const pedestalHeight = 0.37;
    const pedestalGeometry = new THREE.BoxGeometry(0.5, pedestalHeight, 0.56);
    const pickGeometry = new THREE.BoxGeometry(1.02, 1.4, 1.02);
    const metalMaterial = new THREE.MeshStandardMaterial({ color: 0x16191f, roughness: 0.55, metalness: 0.25 });
    const pickMaterial = new THREE.MeshBasicMaterial({
      transparent: true,
      opacity: 0,
      depthWrite: false,
      colorWrite: false,
    });

    this.seats.forEach((seat) => {
      const group = new THREE.Group();
      const seatMaterial = new THREE.MeshStandardMaterial({
        color: COLORS[seat.status],
        roughness: 0.72,
      });

      const cushion = new THREE.Mesh(baseGeometry, seatMaterial);
      cushion.position.y = 0.58;
      cushion.rotation.x = -0.08;
      cushion.castShadow = true;

      const back = new THREE.Mesh(backGeometry, seatMaterial);
      back.position.set(0, 1.04, 0.3);
      back.rotation.x = -0.08;
      back.castShadow = true;

      const leftArm = new THREE.Mesh(armGeometry, metalMaterial);
      leftArm.position.set(-0.43, 0.79, 0.02);
      const rightArm = leftArm.clone();
      rightArm.position.x = 0.43;

      const pedestal = new THREE.Mesh(pedestalGeometry, metalMaterial);
      pedestal.position.set(0, stepTopLocal + pedestalHeight / 2, 0.04);
      pedestal.castShadow = true;
      pedestal.receiveShadow = true;

      const pickTarget = new THREE.Mesh(pickGeometry, pickMaterial);
      pickTarget.position.y = 0.78;
      pickTarget.userData.seatId = seat.id;
      this.pickable.push(pickTarget);

      group.add(pedestal, cushion, back, leftArm, rightArm, pickTarget);
      group.position.set(seat.position.x, seat.position.y, seat.position.z);
      group.userData = { seat, material: seatMaterial };
      this.seatObjects.set(seat.id, group);
      this.scene.add(group);
    });
  }

  bindEvents() {
    this.onPointerDown = (event) => {
      if (this.isMobile && !this.isSeatView) this.hasMobileInteracted = true;
      this.activePointers.add(event.pointerId);
      this.pointerPositions.set(event.pointerId, new THREE.Vector2(event.clientX, event.clientY));
      if (this.isMobile) {
        try {
          this.renderer.domElement.setPointerCapture(event.pointerId);
        } catch {
          // Pointer capture is optional on older mobile browsers.
        }
      }
      if (this.activePointers.size > 1) this.hadMultiTouch = true;
      if (this.activePointers.size === 1) {
        this.pointerStart.set(event.clientX, event.clientY);
        this.lastPinchDistance = null;
      } else if (this.pointerPositions.size === 2) {
        const points = [...this.pointerPositions.values()];
        this.lastPinchDistance = points[0].distanceTo(points[1]);
      }
    };
    this.onPointerUp = (event) => {
      const distance = this.pointerStart.distanceTo(new THREE.Vector2(event.clientX, event.clientY));
      const isSingleTap = this.activePointers.size === 1 && !this.hadMultiTouch;
      const tapThreshold = this.isMobile ? 18 : 6;
      if (isSingleTap && distance < tapThreshold && !this.isSeatView) this.pickSeat(event);
      this.activePointers.delete(event.pointerId);
      this.pointerPositions.delete(event.pointerId);
      if (this.activePointers.size === 0) {
        this.hadMultiTouch = false;
        this.lastPinchDistance = null;
      }
    };
    this.onPointerCancel = (event) => {
      this.activePointers.delete(event.pointerId);
      this.pointerPositions.delete(event.pointerId);
      if (this.activePointers.size === 0) {
        this.hadMultiTouch = false;
        this.lastPinchDistance = null;
      }
    };
    this.onPointerMove = (event) => {
      if (this.isMobile) {
        this.updateMobileGesture(event);
      } else if (event.pointerType === 'mouse') {
        this.updateHover(event);
      }
    };
    this.onResize = () => this.resize(true);
    this.onContextLost = (event) => {
      event.preventDefault();
      this.callbacks.onContextLost?.();
    };
    this.onContextRestored = () => this.callbacks.onContextRestored?.();

    this.renderer.domElement.addEventListener('pointerdown', this.onPointerDown);
    this.renderer.domElement.addEventListener('pointerup', this.onPointerUp);
    this.renderer.domElement.addEventListener('pointercancel', this.onPointerCancel);
    this.renderer.domElement.addEventListener('pointermove', this.onPointerMove);
    this.renderer.domElement.addEventListener('contextmenu', (event) => event.preventDefault());
    this.renderer.domElement.addEventListener('webglcontextlost', this.onContextLost, false);
    this.renderer.domElement.addEventListener('webglcontextrestored', this.onContextRestored, false);
    window.addEventListener('resize', this.onResize);
    window.visualViewport?.addEventListener('resize', this.onResize);
  }

  getSeatFromPointer(event) {
    const bounds = this.renderer.domElement.getBoundingClientRect();
    this.pointer.x = ((event.clientX - bounds.left) / bounds.width) * 2 - 1;
    this.pointer.y = -((event.clientY - bounds.top) / bounds.height) * 2 + 1;
    this.raycaster.setFromCamera(this.pointer, this.camera);
    const hit = this.raycaster.intersectObjects(this.pickable, false)[0];
    return hit ? this.seats.find((seat) => seat.id === hit.object.userData.seatId) : null;
  }

  updateHover(event) {
    if (this.isSeatView) return;
    const seat = this.getSeatFromPointer(event);
    const nextSeat = seat?.status === 'available' ? seat : null;

    if (this.hoveredSeat?.id !== nextSeat?.id) {
      if (this.hoveredSeat && !this.selectedSeats.has(this.hoveredSeat.id)) {
        this.setSeatColor(this.hoveredSeat, COLORS.available);
      }
      this.hoveredSeat = nextSeat;
      if (nextSeat && !this.selectedSeats.has(nextSeat.id)) this.setSeatColor(nextSeat, COLORS.hover);
    }
    this.renderer.domElement.style.cursor = seat ? (seat.status === 'occupied' ? 'not-allowed' : 'pointer') : 'grab';
  }

  pickSeat(event) {
    const seat = this.getSeatFromPointer(event);
    if (!seat) return;
    this.toggleSeat(seat.id);
  }

  toggleSeat(seatId) {
    const seat = this.seats.find((item) => item.id === seatId);
    if (!seat) return;
    if (seat.status === 'occupied') {
      this.callbacks.onUnavailable?.(seat);
      return;
    }
    if (this.selectedSeats.has(seatId)) {
      this.selectedSeats.delete(seatId);
      this.setSeatColor(seat, COLORS.available);
    } else {
      if (this.selectedSeats.size >= this.maxSeats) {
        this.callbacks.onLimit?.(this.maxSeats);
        return;
      }
      this.selectedSeats.set(seatId, seat);
      this.setSeatColor(seat, COLORS.selected);
    }
    this.emitSelection();
  }

  trimTo(count) {
    if (this.selectedSeats.size <= count) return;
    [...this.selectedSeats.keys()].slice(count).forEach((id) => {
      const seat = this.selectedSeats.get(id);
      this.selectedSeats.delete(id);
      this.setSeatColor(seat, COLORS.available);
    });
    this.emitSelection();
  }

  emitSelection() {
    this.callbacks.onSelectionChange?.([...this.selectedSeats.values()]);
  }

  setSeatColor(seat, color) {
    this.seatObjects.get(seat.id)?.userData.material.color.setHex(color);
  }

  previewSelectedSeat() {
    if (!this.selectedSeat || this.isSeatView) return;
    this.isSeatView = true;
    this.controls.enabled = false;
    const seat = this.selectedSeat;
    const targetPosition = {
      x: seat.position.x,
      y: seat.position.y + 1.45,
      z: seat.position.z - 0.18,
    };
    const screenTarget = { x: SCREEN.x, y: SCREEN.y, z: SCREEN.z + 0.05 };
    const seatViewFov = this.getSeatViewFov(seat);
    this.renderer.setPixelRatio(this.getRenderPixelRatio(true));

    gsap.to(this.camera, {
      fov: seatViewFov,
      duration: 1.1,
      ease: 'power2.inOut',
      onUpdate: () => this.camera.updateProjectionMatrix(),
    });

    gsap.to(this.camera.position, {
      ...targetPosition,
      duration: 1.25,
      ease: 'power3.inOut',
      onUpdate: () => this.camera.lookAt(screenTarget.x, screenTarget.y, screenTarget.z),
      onComplete: () => this.callbacks.onEnterSeatView?.(seat),
    });
  }

  getSeatViewFov(seat) {
    const depth = Math.abs(seat.position.z - SCREEN.z);
    const distance = Math.sqrt(depth ** 2 + seat.position.x ** 2);
    const aspect = Math.max(this.camera.aspect, 0.36);
    const effectiveScreenWidth = SCREEN.width * this.getScreenScaleX();
    const halfScreenWithMargin = (effectiveScreenWidth * 1.12) / 2;
    const radians = 2 * Math.atan(halfScreenWithMargin / (distance * aspect));
    const degrees = THREE.MathUtils.radToDeg(radians);
    return THREE.MathUtils.clamp(degrees, this.isMobile ? 58 : 42, this.isMobile ? 96 : 64);
  }

  resetView() {
    const wasInSeatView = this.isSeatView;
    this.isSeatView = false;
    this.controls.enabled = false;
    const overviewFov = this.getOverviewFov();
    this.renderer.setPixelRatio(this.getRenderPixelRatio());

    gsap.to(this.camera, {
      fov: overviewFov,
      duration: wasInSeatView ? 1.05 : 0.65,
      ease: 'power2.inOut',
      onUpdate: () => this.camera.updateProjectionMatrix(),
    });
    gsap.to(this.camera.position, {
      x: this.overviewPosition.x,
      y: this.overviewPosition.y,
      z: this.overviewPosition.z,
      duration: wasInSeatView ? 1.15 : 0.75,
      ease: 'power3.inOut',
      onUpdate: () => this.camera.lookAt(this.overviewTarget),
      onComplete: () => {
        this.controls.target.copy(this.overviewTarget);
        this.controls.enabled = !this.isMobile;
        this.hasMobileInteracted = false;
        this.resetMobileOrbit();
        if (!this.isMobile) this.controls.update();
        this.camera.lookAt(this.overviewTarget);
        if (!this.isMobile) this.controls.saveState();
        this.callbacks.onExitSeatView?.();
      },
    });
  }

  resize(updateDevice = false) {
    const width = Math.max(this.container.clientWidth, 1);
    const height = Math.max(this.container.clientHeight, 1);
    let viewportModeChanged = false;

    if (updateDevice) {
      const nextIsMobile = this.getIsMobile();
      const nextIsPortrait = height >= width;
      const nextViewportMode = nextIsMobile
        ? (nextIsPortrait ? 'mobile-portrait' : 'mobile-landscape')
        : 'desktop';
      if (nextViewportMode !== this.viewportMode) {
        viewportModeChanged = true;
        this.isMobile = nextIsMobile;
        this.isPortrait = nextIsPortrait;
        this.viewportMode = nextViewportMode;
        this.updateOverviewForDevice();
        this.updateControlLimits();
        this.updateScreenForDevice();
        this.renderer.setPixelRatio(this.getRenderPixelRatio(this.isSeatView));
        this.renderer.shadowMap.enabled = !this.isMobile;
        this.renderer.toneMappingExposure = this.isMobile ? 1.28 : 1.05;
        if (this.ambientLight) this.ambientLight.intensity = this.isMobile ? 1.9 : 1.25;
      }
    }

    this.camera.aspect = width / Math.max(height, 1);
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(width, height, false);

    if (this.isSeatView && this.selectedSeat) {
      this.camera.fov = this.getSeatViewFov(this.selectedSeat);
      this.camera.updateProjectionMatrix();
    } else if (viewportModeChanged) {
      this.applyOverviewImmediately();
    }
  }

  animate() {
    this.animationFrame = requestAnimationFrame(() => this.animate());
    if (!this.isSeatView && !this.isMobile && this.controls.enabled) {
      this.controls.update();
    }
    this.renderer.render(this.scene, this.camera);
  }
}
