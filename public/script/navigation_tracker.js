// Navigation Tracker with Radius Detection
class NavigationTracker {
    constructor(map, offices) {
        this.map = map;
        this.offices = offices;
        this.currentNavigation = null;
        this.watchId = null;
        this.userLocation = null;
        this.radiusCircles = new Map(); // Store circle layers
        this.defaultRadius = 50; // Default radius in meters
        this.hasEnteredRadius = false;
        this.routeFinder = null; // Route finder instance
        
        // ============================================
        // TESTING MODE: Static location within school
        // ============================================
        // Static coordinates for testing route system
        this.staticLocation = {
            lat: 10.643401,
            lng: 122.940189
        };
        // ============================================
        
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
        
        // Stop any existing navigation
        this.stopNavigation();

        // ============================================
        // GEOLOCATION CHECK (COMMENTED OUT FOR TESTING)
        // Uncomment when switching back to real-time tracking
        // ============================================
        /*
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }
        */
        // ============================================

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

        // Request location permission and start tracking
        const options = {
            enableHighAccuracy: true,
            timeout: 15000, // Increased to 15 seconds
            maximumAge: 30000 // Accept cached position up to 30 seconds old
        };

        // ============================================
        // TESTING MODE: Using static location
        // ============================================
        console.log('TESTING MODE: Using static location', this.staticLocation);
        const startLat = this.staticLocation.lat;
        const startLng = this.staticLocation.lng;
        const [officeLng, officeLat] = office.lngLat;
        
        console.log('Starting navigation from:', [startLng, startLat], 'to:', [officeLng, officeLat]);
        
        // Calculate and display route
        if (this.routeFinder) {
            console.log('Route finder available, calculating route...');
            this.routeFinder.updateRoute(startLng, startLat, officeLng, officeLat).catch(err => {
                console.error('Error calculating route:', err);
            });
        } else {
            console.warn('Route finder not initialized');
        }
        
        // Simulate location updates with static location
        this.userLocation = { lat: startLat, lng: startLng };
        const mockPosition = {
            coords: {
                latitude: startLat,
                longitude: startLng,
                accuracy: 10
            },
            timestamp: Date.now()
        };
        this.handleLocationUpdate(mockPosition);
        
        // Set up interval to simulate location updates (for testing distance calculations)
        this.staticLocationInterval = setInterval(() => {
            const mockPos = {
                coords: {
                    latitude: this.staticLocation.lat,
                    longitude: this.staticLocation.lng,
                    accuracy: 10
                },
                timestamp: Date.now()
            };
            this.handleLocationUpdate(mockPos);
        }, 3000); // Update every 3 seconds
        
        // ============================================
        // REALTIME TRACKING CODE (COMMENTED OUT)
        // Uncomment the code below to enable real-time geolocation tracking
        // ============================================
        /*
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                try {
                    const startLat = position.coords.latitude;
                    const startLng = position.coords.longitude;
                    const [officeLng, officeLat] = office.lngLat;
                    
                    console.log('Starting navigation from:', [startLng, startLat], 'to:', [officeLng, officeLat]);
                    
                    // Calculate and display route
                    if (this.routeFinder) {
                        console.log('Route finder available, calculating route...');
                        await this.routeFinder.updateRoute(startLng, startLat, officeLng, officeLat);
                    } else {
                        console.warn('Route finder not initialized');
                    }
                    
                    // Start watching position
                    this.watchId = navigator.geolocation.watchPosition(
                        (position) => this.handleLocationUpdate(position),
                        (error) => this.handleLocationError(error),
                        options
                    );
                } catch (error) {
                    console.error('Error in navigation start callback:', error);
                    // Still start watching even if route calculation fails
                    this.watchId = navigator.geolocation.watchPosition(
                        (position) => this.handleLocationUpdate(position),
                        (error) => this.handleLocationError(error),
                        options
                    );
                }
            },
            (error) => {
                console.error('Error getting initial position:', error);
                this.handleLocationError(error);
                // Still start watching even if initial position fails
                this.watchId = navigator.geolocation.watchPosition(
                    (position) => this.handleLocationUpdate(position),
                    (error) => this.handleLocationError(error),
                    options
                );
            },
            options
        );
        */
        // ============================================

        // Show navigation status
        this.showNavigationStatus(office);
    }

    /**
     * Stop current navigation
     */
    stopNavigation() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

// Clear static location interval if in testing mode
if (this.staticLocationInterval) {
    clearInterval(this.staticLocationInterval);
    this.staticLocationInterval = null;
}

// Remove route from map
// Use routeFinder if available, otherwise fallback to this.removeRoute()
if (this.routeFinder && typeof this.routeFinder.removeRoute === "function") {
    this.routeFinder.removeRoute();
} else if (typeof this.removeRoute === "function") {
    this.removeRoute();
}


        if (this.currentNavigation) {
            this.logNavigationEnd(this.currentNavigation.logId, this.hasEnteredRadius);
            this.hideNavigationStatus();
            this.unhighlightRadius(this.currentNavigation.office);
            this.currentNavigation = null;
        }

        this.hasEnteredRadius = false;
        this.lastRouteUpdate = null;
    }

    /**
     * Handle location updates
     */
    handleLocationUpdate(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        this.userLocation = { lat, lng };

        if (!this.currentNavigation) return;

        const office = this.currentNavigation.office;
        const [officeLng, officeLat] = office.lngLat;
        
        // Calculate distance in meters
        const distance = this.calculateDistance(lat, lng, officeLat, officeLng);

        // Update status display
        this.updateNavigationStatus(distance);

        // Update route if enough time has passed since last update
        const now = Date.now();
        if (!this.lastRouteUpdate || (now - this.lastRouteUpdate) > this.routeUpdateInterval) {
            this.updateRoute(lng, lat, officeLng, officeLat);
            this.lastRouteUpdate = now;
        }

        // Check if user entered radius
        if (!this.hasEnteredRadius && distance <= this.currentNavigation.radius) {
            this.hasEnteredRadius = true;
            this.onEnterRadius(office, distance);
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

    /**
     * Fetch route from Mapbox Directions API
     */
    async fetchRoute(startLng, startLat, endLng, endLat) {
        try {
            const accessToken = mapboxgl.accessToken;
            const profile = 'walking'; // Use walking profile for campus navigation
            const url = `https://api.mapbox.com/directions/v5/mapbox/${profile}/${startLng},${startLat};${endLng},${endLat}?geometries=geojson&access_token=${accessToken}`;
            
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Directions API error: ${response.status}`);
            }
            
            const data = await response.json();
            if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                return data.routes[0].geometry;
            }
            return null;
        } catch (error) {
            console.error('Error fetching route:', error);
            return null;
        }
    }

    /**
     * Update route on map
     */
    async updateRoute(startLng, startLat, endLng, endLat) {
        const geometry = await this.fetchRoute(startLng, startLat, endLng, endLat);
        
        if (!geometry) {
            console.warn('Could not fetch route');
            return;
        }

        // Wait for map to be ready
        if (!this.map.loaded()) {
            this.map.once('load', () => this.updateRoute(startLng, startLat, endLng, endLat));
            return;
        }

        const routeGeoJSON = {
            type: 'Feature',
            properties: {},
            geometry: geometry
        };

        // Remove existing route if present
        if (this.map.getSource(this.routeSourceId)) {
            this.map.getSource(this.routeSourceId).setData(routeGeoJSON);
        } else {
            // Add route source
            this.map.addSource(this.routeSourceId, {
                type: 'geojson',
                data: routeGeoJSON
            });

            // Add route layer
            this.map.addLayer({
                id: this.routeLayerId,
                type: 'line',
                source: this.routeSourceId,
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#4f46e5',
                    'line-width': 5,
                    'line-opacity': 0.75
                }
            });
        }
    }

    /**
     * Remove route from map
     */
    removeRoute() {
        if (this.map.getLayer(this.routeLayerId)) {
            this.map.removeLayer(this.routeLayerId);
        }
        if (this.map.getSource(this.routeSourceId)) {
            this.map.removeSource(this.routeSourceId);
        }
    }
}

