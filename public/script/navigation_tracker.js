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
        // Stop any existing navigation
        this.stopNavigation();

        // Check if geolocation is available
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }

        // Get office radius
        const radius = office.radius || this.defaultRadius;
        
        // Highlight the target office radius
        this.highlightRadius(office);

        // Start logging navigation
        const logId = await this.logNavigationStart(office);
        if (!logId) {
            alert('Failed to start navigation logging.');
            return;
        }

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
            timeout: 5000,
            maximumAge: 0
        };

        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.handleLocationUpdate(position),
            (error) => this.handleLocationError(error),
            options
        );

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

        if (this.currentNavigation) {
            this.logNavigationEnd(this.currentNavigation.logId, this.hasEnteredRadius);
            this.hideNavigationStatus();
            this.unhighlightRadius(this.currentNavigation.office);
            this.currentNavigation = null;
        }

        this.hasEnteredRadius = false;
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
        let message = 'Error getting location: ';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                message += 'Permission denied. Please enable location services.';
                break;
            case error.POSITION_UNAVAILABLE:
                message += 'Position unavailable.';
                break;
            case error.TIMEOUT:
                message += 'Request timeout.';
                break;
            default:
                message += 'Unknown error.';
                break;
        }
        
        console.error(message);
        this.showNotification(message, 'error');
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
            top: 80px;
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
            const response = await fetch('../../api/log_navigation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'start',
                    office_id: office.office_id || null,
                    office_name: office.name
                })
            });

            const result = await response.json();
            if (result.status === 'success') {
                return result.log_id;
            }
            return null;
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
}

