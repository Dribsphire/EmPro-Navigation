// Navigation Tracker with Radius Detection
class NavigationTracker {
    constructor(map, offices) {
        this.map = map;
        this.offices = offices;
        this.currentNavigation = null;
        this.watchId = null;
        this.userLocation = null;
        this.userLocationMarker = null; // Marker for user's real-time GPS location
        this.radiusCircles = new Map(); // Store circle layers
        this.defaultRadius = 50; // Default radius in meters
        this.hasEnteredRadius = false;
        this.routeFinder = null; // Route finder instance
        this.routeSourceId = 'navigation-route';
        this.routeLayerId = 'navigation-route-layer';
        
        // Entrance/Starting point coordinates (for route calculation)
        this.entranceLocation = {
            lat: 10.643401,
            lng: 122.940189
        };
        
        // Initialize route finder
        if (typeof RouteFinder !== 'undefined') {
            this.routeFinder = new RouteFinder(map);
            // Load footwalk network when map is ready
            if (this.map.loaded()) {
                this.routeFinder.loadFootwalkNetwork();
            } else {
                this.map.on('load', () => {
                    this.routeFinder.loadFootwalkNetwork();
                });
            }
        }
        
        // Initialize radius circles for all offices after map loads
        if (this.map.loaded()) {
            this.initializeRadiusCircles();
        } else {
            this.map.on('load', () => {
                this.initializeRadiusCircles();
            });
        }
    }

    /**
     * Initialize navigation state restoration
     * Should be called after offices are loaded
     */
    async initializeRestore() {
        // Wait a bit for everything to be ready, then restore
        await new Promise(resolve => setTimeout(resolve, 500));
        await this.restoreNavigationState();
    }

    /**
     * Save navigation state to localStorage
     */
    saveNavigationState() {
        if (this.currentNavigation) {
            const state = {
                office: {
                    name: this.currentNavigation.office.name,
                    office_id: this.currentNavigation.office.office_id,
                    lngLat: this.currentNavigation.office.lngLat,
                    radius: this.currentNavigation.office.radius || this.defaultRadius
                },
                logId: this.currentNavigation.logId,
                startTime: this.currentNavigation.startTime.toISOString(),
                radius: this.currentNavigation.radius,
                hasEnteredRadius: this.hasEnteredRadius
            };
            localStorage.setItem('activeNavigation', JSON.stringify(state));
            console.log('Navigation state saved to localStorage');
        }
    }

    /**
     * Clear navigation state from localStorage
     */
    clearNavigationState() {
        localStorage.removeItem('activeNavigation');
        console.log('Navigation state cleared from localStorage');
    }

    /**
     * Restore navigation state from localStorage
     */
    async restoreNavigationState() {
        try {
            const savedState = localStorage.getItem('activeNavigation');
            if (!savedState) {
                return; // No saved state
            }

            const state = JSON.parse(savedState);
            console.log('Restoring navigation state:', state);

            // Find the office in the current offices list
            const office = this.offices.find(o => 
                (o.office_id && o.office_id == state.office.office_id) ||
                (o.name === state.office.name)
            );

            if (!office) {
                console.warn('Office not found for restored navigation, clearing state');
                this.clearNavigationState();
                return;
            }

            // Restore navigation state
            this.currentNavigation = {
                office: office,
                logId: state.logId,
                startTime: new Date(state.startTime),
                radius: state.radius
            };
            this.hasEnteredRadius = state.hasEnteredRadius || false;

            // Restore route (wait for route finder to be ready)
            const [officeLng, officeLat] = office.lngLat;
            const entranceLat = this.entranceLocation.lat;
            const entranceLng = this.entranceLocation.lng;

            if (this.routeFinder) {
                // Wait for footwalk network to load if not already loaded
                if (!this.routeFinder.footwalkNetwork) {
                    console.log('Waiting for footwalk network to load...');
                    await this.routeFinder.loadFootwalkNetwork();
                }
                console.log('Restoring route from entrance to office...');
                await this.routeFinder.updateRoute(entranceLng, entranceLat, officeLng, officeLat);
            }

            // Highlight the target office radius
            this.highlightRadius(office);

            // Restore navigation status UI
            this.showNavigationStatus(office);

            // Restore map view centered on user's location with specified bearing, pitch, and zoom
            const restoreMapView = (userLat, userLng) => {
                this.map.flyTo({
                    center: [userLng, userLat], // Center on user's location
                    bearing: -149.60,
                    pitch: 39.10,
                    zoom: 18.58,
                    duration: 1000,
                    essential: true
                });
                console.log('Restored map view: centered on user location', [userLng, userLat]);
            };

            // Restart GPS tracking
            if (navigator.geolocation) {
                const options = {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                };

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        console.log('Restored GPS position:', position.coords);
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        this.userLocation = {
                            lat: userLat,
                            lng: userLng
                        };
                        
                        // Create user location marker
                        this.updateUserLocationMarker(userLat, userLng);
                        
                        // Restore map view centered on user's location
                        restoreMapView(userLat, userLng);
                        
                        this.handleLocationUpdate(position);

                        // Start watching position
                        this.watchId = navigator.geolocation.watchPosition(
                            (position) => {
                                this.handleLocationUpdate(position);
                            },
                            (error) => this.handleLocationError(error),
                            options
                        );
                    },
                    (error) => {
                        console.error('Error getting GPS position for restored navigation:', error);
                        // If GPS fails, try to use saved userLocation or fallback
                        if (this.userLocation) {
                            restoreMapView(this.userLocation.lat, this.userLocation.lng);
                        }
                        // Still try to start watching
                        this.watchId = navigator.geolocation.watchPosition(
                            (position) => {
                                this.handleLocationUpdate(position);
                            },
                            (error) => this.handleLocationError(error),
                            options
                        );
                    },
                    options
                );
            }

            console.log('Navigation state restored successfully');
        } catch (error) {
            console.error('Error restoring navigation state:', error);
            this.clearNavigationState();
        }
    }

    /**
     * Initialize radius circles for all offices on the map
     */
    initializeRadiusCircles() {
        this.offices.forEach(office => {
            const radius = office.radius || this.defaultRadius;
            this.addRadiusCircle(office, radius);
        });
    }

    /**
     * Add a radius circle to the map for an office
     */
    addRadiusCircle(office, radiusMeters) {
        if (!office.lngLat || office.lngLat.length !== 2) return;

        const [lng, lat] = office.lngLat;
        
        // Convert meters to degrees (approximate)
        // 1 degree latitude ≈ 111,320 meters
        const radiusDegrees = radiusMeters / 111320;

        // Create circle using GeoJSON
        const circle = this.createCircle(lng, lat, radiusDegrees);
        
        const sourceId = `radius-${office.office_id || office.name}`;
        const layerId = `radius-layer-${office.office_id || office.name}`;

        // Remove existing source/layer if present
        if (this.map.getSource(sourceId)) {
            this.map.removeLayer(layerId);
            this.map.removeSource(sourceId);
        }

        // Add source
        this.map.addSource(sourceId, {
            type: 'geojson',
            data: circle
        });

        // Add layer
        this.map.addLayer({
            id: layerId,
            type: 'fill',
            source: sourceId,
            paint: {
                'fill-color': '#3b82f6',
                'fill-opacity': 0.1,
                'fill-outline-color': '#3b82f6'
            }
        });

        // Store reference
        this.radiusCircles.set(office.office_id || office.name, {
            sourceId,
            layerId,
            radius: radiusMeters,
            center: [lng, lat]
        });
    }

    /**
     * Create a circle GeoJSON from center and radius
     */
    createCircle(centerLng, centerLat, radiusDegrees) {
        const points = 64;
        const coordinates = [];

        for (let i = 0; i < points; i++) {
            const angle = (i * 360) / points;
            const radians = (angle * Math.PI) / 180;
            
            const lat = centerLat + radiusDegrees * Math.cos(radians);
            const lng = centerLng + radiusDegrees * Math.sin(radians) / Math.cos(centerLat * Math.PI / 180);
            
            coordinates.push([lng, lat]);
        }
        
        // Close the circle
        coordinates.push(coordinates[0]);

        return {
            type: 'Feature',
            geometry: {
                type: 'Polygon',
                coordinates: [coordinates]
            }
        };
    }

    /**
     * Start navigation to an office
     */
    async startNavigation(office) {
        console.log('startNavigation called with:', office);
        
        // Validate office data
        if (!office) {
            console.error('No office provided to startNavigation');
            alert('Error: No office selected for navigation.');
            return;
        }
        
        if (!office.lngLat || !Array.isArray(office.lngLat) || office.lngLat.length !== 2) {
            console.error('Invalid office coordinates:', office.lngLat);
            alert('Error: Invalid office location data.');
            return;
        }
        
        if (!office.name && !office.office_id) {
            console.error('Office missing name and office_id:', office);
            alert('Error: Office information is incomplete.');
            return;
        }
        
        // Check if there's an active navigation to a different office
        if (this.currentNavigation) {
            const currentOffice = this.currentNavigation.office;
            const isSameOffice = (currentOffice.office_id && currentOffice.office_id == office.office_id) ||
                                (currentOffice.name === office.name);
            
            if (!isSameOffice) {
                // Show confirmation modal before canceling current navigation
                const confirmed = await this.showCancelNavigationModal(currentOffice.name, office.name);
                if (!confirmed) {
                    console.log('User cancelled navigation switch');
                    return; // User cancelled, don't start new navigation
                }
            }
        }
        
        // Stop any existing navigation (if confirmed or same office)
        this.stopNavigation();

        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }

        // Get office radius
        const radius = office.radius || this.defaultRadius;
        
        // Highlight the target office radius
        this.highlightRadius(office);

        // Start logging navigation
        console.log('Logging navigation start for office:', office.name || office.office_id);
        const logId = await this.logNavigationStart(office);
        if (!logId) {
            console.error('Failed to get log ID from API');
            alert('Failed to start navigation logging. Please try again.');
            return;
        }
        
        console.log('Navigation log created with ID:', logId);

        this.currentNavigation = {
            office: office,
            logId: logId,
            startTime: new Date(),
            radius: radius
        };

        this.hasEnteredRadius = false;

        // Save navigation state to localStorage for persistence
        this.saveNavigationState();

        // Request location permission and start tracking with real-time GPS
        const options = {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0 // Always get fresh GPS position
        };

        // Get initial real GPS position and start navigation
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                try {
                    const startLat = position.coords.latitude;
                    const startLng = position.coords.longitude;
                    const [officeLng, officeLat] = office.lngLat;
                    
                    console.log('Starting navigation from real GPS location:', [startLng, startLat], 'to:', [officeLng, officeLat]);
                    
                    // Update user location
                    this.userLocation = { lat: startLat, lng: startLng };
                    
                    // Create user location marker immediately with real GPS position
                    console.log('Creating user location marker with real GPS:', startLat, startLng);
                    this.updateUserLocationMarker(startLat, startLng);
                    
                    // Calculate and display route from real GPS location to office
                    if (this.routeFinder) {
                        console.log('Route finder available, calculating route from real GPS location...');
                        await this.routeFinder.updateRoute(startLng, startLat, officeLng, officeLat);
                    } else {
                        console.warn('Route finder not initialized');
                    }
                    
                    // Update map view centered on user's real GPS location
                    const updateMapView = (userLat, userLng) => {
                        this.map.flyTo({
                            center: [userLng, userLat],
                            bearing: -149.60,
                            pitch: 39.10,
                            zoom: 18.58,
                            duration: 1000,
                            essential: true
                        });
                        console.log('Map view updated: centered on real GPS location', [userLng, userLat]);
                    };
                    updateMapView(startLat, startLng);
                    
                    // Handle initial location update (this will update the marker position)
                    this.handleLocationUpdate(position);
                    
                    // Start watching position for real-time updates
                    this.watchId = navigator.geolocation.watchPosition(
                        (position) => {
                            console.log('Real GPS position update:', position.coords);
                            this.handleLocationUpdate(position);
                        },
                        (error) => this.handleLocationError(error),
                        options
                    );
                } catch (error) {
                    console.error('Error in navigation start callback:', error);
                    // Still start watching even if route calculation fails
                    this.watchId = navigator.geolocation.watchPosition(
                        (position) => {
                            console.log('Real GPS position update:', position.coords);
                            this.handleLocationUpdate(position);
                        },
                        (error) => this.handleLocationError(error),
                        options
                    );
                }
            },
            (error) => {
                console.error('Error getting initial real GPS position:', error);
                this.handleLocationError(error);
                // Still try to start watching even if initial position fails
                this.watchId = navigator.geolocation.watchPosition(
                    (position) => {
                        console.log('Real GPS position update:', position.coords);
                        this.handleLocationUpdate(position);
                    },
                    (error) => this.handleLocationError(error),
                    options
                );
            },
            options
        );

        // Show navigation status
        this.showNavigationStatus(office);
        
        // Note: Map view will be updated in the GPS callback with real-time location
    }

    /**
     * Stop current navigation
     */
    stopNavigation() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        // Get current user location before removing marker
        const currentUserLocation = this.userLocation;
        
        // Remove user location marker
        this.removeUserLocationMarker();
        
        // Show UserLocationTracker marker again when navigation stops
        // Update it with the real GPS location from navigation instead of static location
        if (window.userLocationTracker) {
            // Stop static location interval to prevent it from overriding real GPS location
            if (window.userLocationTracker.staticLocationInterval) {
                clearInterval(window.userLocationTracker.staticLocationInterval);
                window.userLocationTracker.staticLocationInterval = null;
                console.log('Stopped static location interval');
            }
            
            if (currentUserLocation && currentUserLocation.lat && currentUserLocation.lng) {
                // Update UserLocationTracker with the real GPS location from navigation
                console.log('Updating UserLocationTracker with real GPS location:', currentUserLocation);
                
                // Update the current position
                window.userLocationTracker.currentPosition = {
                    lat: currentUserLocation.lat,
                    lng: currentUserLocation.lng,
                    accuracy: 10, // Use a reasonable default accuracy
                    timestamp: new Date()
                };
                
                // Create or update the marker with real location
                if (!window.userLocationTracker.userMarker) {
                    window.userLocationTracker.createUserMarker(currentUserLocation.lng, currentUserLocation.lat);
                } else {
                    window.userLocationTracker.userMarker.setLngLat([currentUserLocation.lng, currentUserLocation.lat]);
                    const markerEl = window.userLocationTracker.userMarker.getElement();
                    if (markerEl) {
                        markerEl.style.display = 'block';
                    }
                    console.log('UserLocationTracker marker updated to real GPS location:', currentUserLocation);
                }
                
                // Start real GPS tracking instead of static location
                window.userLocationTracker.startRealGPSTracking();
            } else if (window.userLocationTracker.userMarker) {
                // If no location available, just show the existing marker
                const markerEl = window.userLocationTracker.userMarker.getElement();
                if (markerEl) {
                    markerEl.style.display = 'block';
                    console.log('UserLocationTracker marker shown again after navigation stopped');
                }
            }
        }

        // Remove route from map - use RouteFinder only
        if (this.routeFinder && typeof this.routeFinder.removeRoute === "function") {
            this.routeFinder.removeRoute();
        }
        // Also remove NavigationTracker's route if it exists (for cleanup)
        if (this.map.getLayer(this.routeLayerId)) {
            this.map.removeLayer(this.routeLayerId);
        }
        if (this.map.getSource(this.routeSourceId)) {
            this.map.removeSource(this.routeSourceId);
        }

        if (this.currentNavigation) {
            this.logNavigationEnd(this.currentNavigation.logId, this.hasEnteredRadius);
            this.hideNavigationStatus();
            this.unhighlightRadius(this.currentNavigation.office);
            this.currentNavigation = null;
        }

        this.hasEnteredRadius = false;
        this.lastRouteUpdate = null;

        // Clear navigation state from localStorage
        this.clearNavigationState();
    }

    /**
     * Create or update user location marker for real-time GPS position
     */
    updateUserLocationMarker(lat, lng) {
        // Determine marker image path
        const currentPath = window.location.pathname;
        let markerPath;
        if (currentPath.includes('/student/') || currentPath.includes('/guest/')) {
            markerPath = '../icons/default_marker.png';
        } else if (currentPath.includes('/admin/')) {
            markerPath = '../../icons/default_marker.png';
        } else {
            markerPath = 'icons/default_marker.png';
        }
        
        if (!this.userLocationMarker) {
            // Create marker element container
            const el = document.createElement('div');
            el.className = 'navigation-user-location-marker';
            el.style.cssText = `
                position: relative;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: flex-end;
                justify-content: center;
                cursor: pointer;
            `;
            
            // Add animation styles if not already added
            if (!document.getElementById('navigation-user-location-animations')) {
                const animStyle = document.createElement('style');
                animStyle.id = 'navigation-user-location-animations';
                animStyle.textContent = `
                    @keyframes userLocationPulse {
                        0%, 100% {
                            transform: scale(1);
                        }
                        50% {
                            transform: scale(1.15);
                        }
                    }
                    @keyframes userLocationRing {
                        0% {
                            transform: translate(-50%, -50%) scale(0.8);
                            opacity: 0.6;
                        }
                        100% {
                            transform: translate(-50%, -50%) scale(1.5);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(animStyle);
            }
            
            // Add pulsing ring effect around the marker (add before img so it's behind)
            const ring = document.createElement('div');
            ring.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 60px;
                height: 60px;
                border-radius: 50%;
                border: 2px solid #3b82f6;
                opacity: 0.4;
                animation: userLocationRing 2s infinite;
                pointer-events: none;
                z-index: 1;
            `;
            el.appendChild(ring);
            
            // Use img tag for better reliability
            const img = document.createElement('img');
            img.src = markerPath;
            img.alt = 'Your Location';
            img.style.cssText = `
                width: 40px;
                height: 40px;
                display: block;
                cursor: pointer;
                animation: userLocationPulse 2s infinite;
                filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.4));
                position: relative;
                z-index: 2;
                object-fit: contain;
            `;
            
            // Handle image load error
            img.onerror = () => {
                console.error('Failed to load marker image from:', markerPath);
                // Try alternative paths
                const altPaths = [
                    '../icons/default_marker.png',
                    '../../icons/default_marker.png',
                    'icons/default_marker.png',
                    '/icons/default_marker.png'
                ];
                
                let pathIndex = 0;
                const tryNextPath = () => {
                    if (pathIndex < altPaths.length) {
                        const altPath = altPaths[pathIndex++];
                        console.log('Trying alternative path:', altPath);
                        img.src = altPath;
                    } else {
                        // Fallback to colored circle if all paths fail
                        console.warn('All image paths failed, using fallback marker');
                        img.style.display = 'none';
                        el.style.cssText = `
                            width: 20px;
                            height: 20px;
                            border-radius: 50%;
                            background: #3b82f6;
                            border: 3px solid white;
                            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
                            cursor: pointer;
                        `;
                    }
                };
                
                img.onerror = tryNextPath;
                tryNextPath();
            };
            
            img.onload = () => {
                console.log('✓ Marker image loaded successfully from:', img.src);
            };
            
            // Append img to el (ring is already appended)
            el.appendChild(img);
            
            // Create popup
            const popup = new mapboxgl.Popup({ offset: 25 })
                .setHTML(`
                    <div style="padding: 8px; text-align: center;">
                        <div style="font-weight: 600; color: #333; margin-bottom: 4px;">Your Location</div>
                        <div style="font-size: 12px; color: #666;">Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}</div>
                    </div>
                `);
            
            // Create marker with anchor at center (ensures icon point aligns with GPS coordinates)
            this.userLocationMarker = new mapboxgl.Marker({
                element: el,
                anchor: 'center'
            })
                .setLngLat([lng, lat])
                .setPopup(popup)
                .addTo(this.map);
            
            console.log('✓ User location marker created and added to map at:', lng, lat);
            
            // Verify marker is visible
            setTimeout(() => {
                if (this.userLocationMarker && this.userLocationMarker.getElement()) {
                    const markerEl = this.userLocationMarker.getElement();
                    const rect = markerEl.getBoundingClientRect();
                    console.log('Marker element bounds:', rect);
                    if (rect.width === 0 || rect.height === 0) {
                        console.warn('Marker element has zero dimensions!');
                        markerEl.style.display = 'block';
                        markerEl.style.visibility = 'visible';
                    } else {
                        console.log('✓ Marker is visible on map');
                    }
                }
            }, 500);
        } else {
            // Update existing marker position
            this.userLocationMarker.setLngLat([lng, lat]);
            
            // Update popup content
            const popup = new mapboxgl.Popup({ offset: 25 })
                .setHTML(`
                    <div style="padding: 8px; text-align: center;">
                        <div style="font-weight: 600; color: #333; margin-bottom: 4px;">Your Location</div>
                        <div style="font-size: 12px; color: #666;">Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}</div>
                    </div>
                `);
            this.userLocationMarker.setPopup(popup);
            console.log('User location marker updated to:', lng, lat);
        }
    }

    /**
     * Remove user location marker
     */
    removeUserLocationMarker() {
        if (this.userLocationMarker) {
            this.userLocationMarker.remove();
            this.userLocationMarker = null;
            console.log('User location marker removed');
        }
    }

    /**
     * Handle location updates
     */
    handleLocationUpdate(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        this.userLocation = { lat, lng };

        // Update user location marker with real-time GPS position
        this.updateUserLocationMarker(lat, lng);

        if (!this.currentNavigation) return;

        const office = this.currentNavigation.office;
        const [officeLng, officeLat] = office.lngLat;
        
        // Calculate distance in meters
        const distance = this.calculateDistance(lat, lng, officeLat, officeLng);

        // Update status display
        this.updateNavigationStatus(distance);

        // Update route if enough time has passed since last update
        // Use RouteFinder instead of NavigationTracker's updateRoute to avoid duplicate routes
        const now = Date.now();
        if (!this.lastRouteUpdate || (now - this.lastRouteUpdate) > this.routeUpdateInterval) {
            if (this.routeFinder) {
                // Only update route using RouteFinder (footwalk network)
                this.routeFinder.updateRoute(lng, lat, officeLng, officeLat).catch(err => {
                    console.error('Error updating route:', err);
                });
            }
            this.lastRouteUpdate = now;
        }

        // Check if user entered radius
        if (!this.hasEnteredRadius && distance <= this.currentNavigation.radius) {
            this.hasEnteredRadius = true;
            this.onEnterRadius(office, distance);
            // Update saved state when radius is entered
            this.saveNavigationState();
        }
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371000; // Earth radius in meters
        const dLat = this.toRad(lat2 - lat1);
        const dLng = this.toRad(lng2 - lng1);
        
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
                  Math.sin(dLng / 2) * Math.sin(dLng / 2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    /**
     * Convert degrees to radians
     */
    toRad(degrees) {
        return degrees * (Math.PI / 180);
    }

    /**
     * Called when user enters the office radius
     */
    onEnterRadius(office, distance) {
        console.log(`Entered radius of ${office.name}! Distance: ${distance.toFixed(2)}m`);
        
        // Show notification
        this.showNotification(`You've reached ${office.name}!`, 'success');
        
        // Log that user reached the destination
        this.logNavigationReached(office);
        
        // Automatically stop navigation after a short delay (3 seconds)
        // This gives the user time to see the notification and understand they've arrived
        setTimeout(() => {
            if (this.currentNavigation && this.hasEnteredRadius) {
                console.log('Auto-stopping navigation after reaching destination');
                this.stopNavigation();
                
                // Show a follow-up notification
                this.showNotification('Navigation completed!', 'success');
            }
        }, 3000); // 3 second delay
    }

    /**
     * Handle geolocation errors
     */
    handleLocationError(error) {
        // Don't show notifications for timeout errors during active navigation
        // as they're common and the system will retry automatically
        if (error.code === error.TIMEOUT) {
            // Timeout is expected sometimes, just log it quietly
            console.log('Location update timeout - will retry automatically');
            return;
        }

        let message = 'Error getting location: ';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                message += 'Permission denied. Please enable location services.';
                this.showNotification(message, 'error');
                break;
            case error.POSITION_UNAVAILABLE:
                message += 'Position unavailable. Please check your device settings.';
                this.showNotification(message, 'error');
                break;
            default:
                message += 'Unknown error.';
                console.error(message);
                break;
        }
    }

    /**
     * Highlight radius circle for target office
     */
    highlightRadius(office) {
        const circle = this.radiusCircles.get(office.office_id || office.name);
        if (circle && this.map.getLayer(circle.layerId)) {
            this.map.setPaintProperty(circle.layerId, 'fill-color', '#10b981');
            this.map.setPaintProperty(circle.layerId, 'fill-opacity', 0.2);
            this.map.setPaintProperty(circle.layerId, 'fill-outline-color', '#10b981');
        }
    }

    /**
     * Unhighlight radius circle
     */
    unhighlightRadius(office) {
        const circle = this.radiusCircles.get(office.office_id || office.name);
        if (circle && this.map.getLayer(circle.layerId)) {
            this.map.setPaintProperty(circle.layerId, 'fill-color', '#3b82f6');
            this.map.setPaintProperty(circle.layerId, 'fill-opacity', 0.1);
            this.map.setPaintProperty(circle.layerId, 'fill-outline-color', '#3b82f6');
        }
    }

    /**
     * Show navigation status UI
     */
    showNavigationStatus(office) {
        // Remove existing status if any
        this.hideNavigationStatus();

        const statusDiv = document.createElement('div');
        statusDiv.id = 'navigation-status';
        statusDiv.style.cssText = `
            position: fixed;
            top: 167px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
            min-width: 250px;
            font-family: Arial, sans-serif;
        `;
        
        statusDiv.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin: 0; font-size: 16px; color: #333;">Navigating to ${office.name}</h3>
                <button id="stop-navigation-btn" style="
                    background: #ef4444;
                    color: white;
                    border: none;
                    padding: 5px 10px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                ">Stop</button>
            </div>
            <div id="navigation-distance" style="color: #666; font-size: 14px;">Calculating distance...</div>
            <div id="navigation-status-indicator" style="margin-top: 10px; font-size: 12px; color: #666;">
                <span id="radius-status">Not in range</span>
            </div>
        `;

        document.body.appendChild(statusDiv);

        // Add stop button handler
        document.getElementById('stop-navigation-btn').addEventListener('click', () => {
            this.stopNavigation();
        });
    }

    /**
     * Update navigation status with distance
     */
    updateNavigationStatus(distance) {
        const distanceDiv = document.getElementById('navigation-distance');
        const statusIndicator = document.getElementById('radius-status');
        
        if (distanceDiv) {
            if (distance < 1000) {
                distanceDiv.textContent = `Distance: ${distance.toFixed(0)}m`;
            } else {
                distanceDiv.textContent = `Distance: ${(distance / 1000).toFixed(2)}km`;
            }
        }

        if (statusIndicator) {
            if (distance <= this.currentNavigation.radius) {
                statusIndicator.textContent = '✓ You are in range!';
                statusIndicator.style.color = '#10b981';
            } else {
                statusIndicator.textContent = 'Not in range yet';
                statusIndicator.style.color = '#666';
            }
        }
    }

    /**
     * Hide navigation status UI
     */
    hideNavigationStatus() {
        const statusDiv = document.getElementById('navigation-status');
        if (statusDiv) {
            statusDiv.remove();
        }
    }

    /**
     * Show confirmation modal for canceling current navigation
     * @param {string} currentOfficeName - Name of currently navigating office
     * @param {string} newOfficeName - Name of new office to navigate to
     * @returns {Promise<boolean>} - Returns true if user confirms, false if cancelled
     */
    showCancelNavigationModal(currentOfficeName, newOfficeName) {
        return new Promise((resolve) => {
            // Create modal overlay
            const overlay = document.createElement('div');
            overlay.id = 'cancel-navigation-modal-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 10000;
                display: flex;
                justify-content: center;
                align-items: center;
                animation: fadeIn 0.2s ease;
            `;

            // Create modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                background: white;
                border-radius: 12px;
                padding: 24px;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                animation: slideUp 0.3s ease;
                font-family: 'Poppins', Arial, sans-serif;
            `;

            modal.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <h2 style="margin: 0 0 12px 0; font-size: 20px; color: #1f2937; font-weight: 600;">
                        Cancel Current Navigation?
                    </h2>
                    <p style="margin: 0; color: #6b7280; font-size: 14px; line-height: 1.5;">
                        You are currently navigating to <strong style="color: #1f2937;">${currentOfficeName}</strong>.
                        Do you want to cancel this navigation and start navigating to <strong style="color: #1f2937;">${newOfficeName}</strong> instead?
                    </p>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button id="cancel-nav-cancel-btn" style="
                        background: #f3f4f6;
                        color: #374151;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 14px;
                        font-weight: 500;
                        transition: background 0.2s;
                    " onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                        Keep Current
                    </button>
                    <button id="cancel-nav-confirm-btn" style="
                        background: #ef4444;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 14px;
                        font-weight: 500;
                        transition: background 0.2s;
                    " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                        Cancel & Switch
                    </button>
                </div>
            `;

            // Add animations
            if (!document.getElementById('modal-animations')) {
                const style = document.createElement('style');
                style.id = 'modal-animations';
                style.textContent = `
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideUp {
                        from {
                            transform: translateY(20px);
                            opacity: 0;
                        }
                        to {
                            transform: translateY(0);
                            opacity: 1;
                        }
                    }
                `;
                document.head.appendChild(style);
            }

            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            // Handle cancel button (keep current navigation)
            document.getElementById('cancel-nav-cancel-btn').addEventListener('click', () => {
                overlay.style.animation = 'fadeIn 0.2s ease reverse';
                setTimeout(() => {
                    overlay.remove();
                }, 200);
                resolve(false);
            });

            // Handle confirm button (cancel current and switch)
            document.getElementById('cancel-nav-confirm-btn').addEventListener('click', () => {
                overlay.style.animation = 'fadeIn 0.2s ease reverse';
                setTimeout(() => {
                    overlay.remove();
                }, 200);
                resolve(true);
            });

            // Close on overlay click (outside modal)
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.style.animation = 'fadeIn 0.2s ease reverse';
                    setTimeout(() => {
                        overlay.remove();
                    }, 200);
                    resolve(false);
                }
            });

            // Close on Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    overlay.style.animation = 'fadeIn 0.2s ease reverse';
                    setTimeout(() => {
                        overlay.remove();
                    }, 200);
                    document.removeEventListener('keydown', handleEscape);
                    resolve(false);
                }
            };
            document.addEventListener('keydown', handleEscape);
        });
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1001;
            font-family: Arial, sans-serif;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    /**
     * Log navigation start
     */
    async logNavigationStart(office) {
        try {
            const requestData = {
                action: 'start',
                office_id: office.office_id || null,
                office_name: office.name || null
            };
            
            console.log('Sending navigation start request:', requestData);
            
            const response = await fetch('../../api/log_navigation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                console.error('API response not OK:', response.status, response.statusText);
                const errorText = await response.text();
                console.error('Error response:', errorText);
                return null;
            }

            const result = await response.json();
            console.log('Navigation start API response:', result);
            
            if (result.status === 'success') {
                return result.log_id;
            } else {
                console.error('API returned error status:', result.message || 'Unknown error');
                return null;
            }
        } catch (error) {
            console.error('Error logging navigation start:', error);
            return null;
        }
    }

    /**
     * Log navigation end
     */
    async logNavigationEnd(logId, reached) {
        try {
            await fetch('../../api/log_navigation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'end',
                    log_id: logId,
                    status: reached ? 'completed' : 'cancelled'
                })
            });
        } catch (error) {
            console.error('Error logging navigation end:', error);
        }
    }

    /**
     * Log when user reaches destination
     */
    async logNavigationReached(office) {
        try {
            await fetch('../../api/log_navigation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'reached',
                    office_id: office.office_id || null,
                    office_name: office.name
                })
            });
        } catch (error) {
            console.error('Error logging navigation reached:', error);
        }
    }

    // Note: Route drawing is now handled exclusively by RouteFinder class
    // NavigationTracker no longer draws routes directly to avoid duplicate route lines
    // All route drawing uses the footwalk network from chmsu.geojson (including footwalk and hidden_footwalk)
}

