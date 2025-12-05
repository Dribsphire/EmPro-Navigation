/**
 * User Location Tracker
 * Displays user's current location on the map with a real-time updating marker
 */
class UserLocationTracker {
    constructor(map) {
        this.map = map;
        this.userMarker = null;
        this.watchId = null;
        this.isTracking = false;
        this.currentPosition = null;
        this.updateInterval = 3000; // Update every 3 seconds
        this.lastUpdateTime = null;
        this.hasCentered = false;
        
        // ============================================
        // TESTING MODE: Static location within school
        // ============================================
        // Static coordinates for testing
        this.staticLocation = {
            lat: 10.643401,
            lng: 122.940189
        };
        // ============================================
        
        // Create location status UI
        this.createLocationStatusUI();
    }

    /**
     * Create location status indicator UI
     */
    createLocationStatusUI() {
        const statusDiv = document.createElement('div');
        statusDiv.id = 'user-location-status';
        statusDiv.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: rgba(17, 18, 26, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        `;
        
        statusDiv.innerHTML = `
            <div id="location-indicator" style="
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #ef4444;
                animation: pulse 2s infinite;
            "></div>
            <div style="display: flex; flex-direction: column; gap: 2px;">
                <span id="location-status-text" style="color: #f3f4f6; font-size: 14px; font-weight: 600;">Requesting location...</span>
                <span id="location-accuracy" style="color: #9ca3af; font-size: 11px;"></span>
            </div>
            <button id="toggle-location-btn" style="
                background: #4f46e5;
                border: none;
                color: white;
                padding: 6px 12px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 600;
                margin-left: 8px;
            ">Stop</button>
        `;
        
        // Add pulse animation and mobile responsive styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% {
                    opacity: 1;
                    transform: scale(1);
                }
                50% {
                    opacity: 0.7;
                    transform: scale(1.2);
                }
            }
            @keyframes pulse-green {
                0%, 100% {
                    opacity: 1;
                    transform: scale(1);
                    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
                }
                50% {
                    opacity: 0.9;
                    transform: scale(1.1);
                    box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
                }
            }
            /* Hide user location status on mobile devices */
            @media (max-width: 768px) {
                #user-location-status {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(statusDiv);
        
        // Add toggle button handler
        document.getElementById('toggle-location-btn').addEventListener('click', () => {
            if (this.isTracking) {
                this.stopTracking();
            } else {
                this.startTracking();
            }
        });
    }

    /**
     * Create user location marker
     */
    createUserMarker(lng, lat) {
        // Remove existing marker if any
        if (this.userMarker) {
            this.userMarker.remove();
        }

        console.log('Creating user location marker at:', lng, lat);

        // Create custom marker element using default_marker.png
        const el = document.createElement('div');
        el.className = 'user-location-marker';
        
        // Determine the correct path based on where the script is included
        // Script paths are relative to the HTML page that includes them
        // From student_index.php or guest_index.php: ../icons/default_marker.png
        // From root pages: icons/default_marker.png
        const currentPath = window.location.pathname;
        let markerPath;
        if (currentPath.includes('/student/') || currentPath.includes('/guest/')) {
            markerPath = '../icons/default_marker.png';
        } else if (currentPath.includes('/admin/')) {
            markerPath = '../../icons/default_marker.png';
        } else {
            markerPath = 'icons/default_marker.png';
        }
        
        console.log('Using marker path:', markerPath);
        
        // Container styling - position relative for absolute children
        el.style.cssText = `
            position: relative;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            cursor: pointer;
        `;
        
        // Use img tag for better reliability than background-image
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
        
        // Create fallback div (will be shown if image fails)
        const fallback = document.createElement('div');
        fallback.id = 'user-location-fallback';
        fallback.style.cssText = `
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #3b82f6;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            animation: userLocationPulse 2s infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            display: none;
        `;
        
        // Handle image load error
        img.onerror = () => {
            console.error('Failed to load marker image from:', markerPath);
            console.log('Trying alternative paths...');
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
                    // All paths failed, show fallback
                    console.warn('All image paths failed, showing fallback marker');
                    img.style.display = 'none';
                    fallback.style.display = 'block';
                }
            };
            
            img.onerror = tryNextPath;
            tryNextPath();
        };
        
        img.onload = () => {
            console.log('✓ Marker image loaded successfully from:', img.src);
            fallback.style.display = 'none';
        };
        
        el.appendChild(img);
        el.appendChild(fallback);
        
        // Add pulsing ring effect around the marker
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
        
        // Add animation styles
        if (!document.getElementById('user-location-animations')) {
            const animStyle = document.createElement('style');
            animStyle.id = 'user-location-animations';
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

        // Create popup
        const popup = new mapboxgl.Popup({ offset: 25 })
            .setHTML(`
                <div style="padding: 8px; text-align: center;">
                    <div style="font-weight: 600; color: #333; margin-bottom: 4px;">Your Location</div>
                    <div style="font-size: 12px; color: #666;">Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}</div>
                </div>
            `);

        // Ensure map is loaded before adding marker
        if (!this.map.loaded()) {
            console.log('Map not loaded yet, waiting...');
            this.map.once('load', () => {
                this.createMarkerElement(el, popup, lng, lat);
            });
        } else {
            this.createMarkerElement(el, popup, lng, lat);
        }

        return this.userMarker;
    }

    /**
     * Create and add marker element to map
     */
    createMarkerElement(el, popup, lng, lat) {
        // Create marker with anchor at bottom center (for pin markers, the point should be at the location)
        // This ensures the pin point aligns with the actual location
        this.userMarker = new mapboxgl.Marker({
            element: el,
            anchor: 'bottom' // Anchor at bottom center so pin point aligns with location
        })
            .setLngLat([lng, lat])
            .setPopup(popup)
            .addTo(this.map);

        console.log('User location marker created and added to map at:', lng, lat);
        
        // Verify marker is visible
        setTimeout(() => {
            if (this.userMarker && this.userMarker.getElement()) {
                const markerEl = this.userMarker.getElement();
                const rect = markerEl.getBoundingClientRect();
                console.log('Marker element bounds:', rect);
                console.log('Marker element computed styles:', window.getComputedStyle(markerEl));
                
                if (rect.width === 0 || rect.height === 0) {
                    console.warn('Marker element has zero dimensions!');
                    // Try to force visibility
                    markerEl.style.display = 'block';
                    markerEl.style.visibility = 'visible';
                } else {
                    console.log('✓ Marker is visible on map');
                }
                
                // Check if image loaded
                const img = markerEl.querySelector('img');
                if (img) {
                    console.log('Image src:', img.src);
                    console.log('Image complete:', img.complete);
                    console.log('Image natural width:', img.naturalWidth);
                    if (!img.complete || img.naturalWidth === 0) {
                        console.warn('Image may not have loaded properly');
                    }
                }
            } else {
                console.error('Marker or marker element not found!');
            }
        }, 500);
    }

    /**
     * Update location status UI
     */
    updateStatusUI(status, accuracy = null) {
        const statusText = document.getElementById('location-status-text');
        const accuracyText = document.getElementById('location-accuracy');
        const indicator = document.getElementById('location-indicator');
        const toggleBtn = document.getElementById('toggle-location-btn');

        if (statusText) {
            statusText.textContent = status;
        }

        if (accuracy !== null && accuracyText) {
            accuracyText.textContent = `Accuracy: ±${Math.round(accuracy)}m`;
        }

        if (indicator) {
            if (status.includes('Tracking') || status.includes('Active')) {
                indicator.style.background = '#22c55e';
                indicator.style.animation = 'pulse-green 2s infinite';
            } else if (status.includes('Error') || status.includes('Denied')) {
                indicator.style.background = '#ef4444';
                indicator.style.animation = 'pulse 2s infinite';
            } else {
                indicator.style.background = '#f59e0b';
                indicator.style.animation = 'pulse 2s infinite';
            }
        }

        if (toggleBtn) {
            toggleBtn.textContent = this.isTracking ? 'Stop' : 'Start';
        }
    }

    /**
     * Handle location update
     */
    handleLocationUpdate(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;
        const timestamp = new Date(position.timestamp);

        console.log('Location update received:', { lat, lng, accuracy });

        this.currentPosition = { lat, lng, accuracy, timestamp };

        // Update marker position or create if it doesn't exist
        if (this.userMarker) {
            // Update existing marker position
            this.userMarker.setLngLat([lng, lat]);
            console.log('Updated existing marker position');
        } else {
            // Create new marker
            console.log('Creating new marker...');
            this.createUserMarker(lng, lat);
        }

        // Update status
        this.updateStatusUI('Location Active', accuracy);
        this.lastUpdateTime = Date.now();

        // Center map on user location (only first time)
        if (!this.hasCentered) {
            console.log('Centering map on user location');
            this.map.flyTo({
                center: [lng, lat],
                zoom: 18,
                duration: 1500
            });
            this.hasCentered = true;
        }
    }

    /**
     * Handle location error
     */
    handleLocationError(error) {
        let message = 'Location Error: ';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                message += 'Permission denied. Please enable location services.';
                this.updateStatusUI('Permission Denied');
                break;
            case error.POSITION_UNAVAILABLE:
                message += 'Position unavailable.';
                this.updateStatusUI('Position Unavailable');
                break;
            case error.TIMEOUT:
                message += 'Request timeout.';
                this.updateStatusUI('Request Timeout');
                break;
            default:
                message += 'Unknown error.';
                this.updateStatusUI('Location Error');
                break;
        }
        
        console.error(message);
        
        // Show notification
        this.showNotification(message, 'error');
    }

    /**
     * Start tracking user location
     */
    startTracking() {
        if (this.isTracking) {
            return;
        }

        // ============================================
        // TESTING MODE: Using static location
        // ============================================
        console.log('TESTING MODE: Using static location', this.staticLocation);
        this.isTracking = true;
        this.updateStatusUI('Location Active', 10);
        
        // Use static location
        const mockPosition = {
            coords: {
                latitude: this.staticLocation.lat,
                longitude: this.staticLocation.lng,
                accuracy: 10
            },
            timestamp: Date.now()
        };
        
        this.handleLocationUpdate(mockPosition);
        
        // Simulate location updates with static location
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
        }, this.updateInterval);
        
        // ============================================
        // REALTIME TRACKING CODE (COMMENTED OUT)
        // Uncomment the code below to enable real-time geolocation tracking
        // ============================================
        /*
        if (!navigator.geolocation) {
            this.updateStatusUI('Geolocation Not Supported');
            this.showNotification('Geolocation is not supported by your browser.', 'error');
            return;
        }

        this.isTracking = true;
        this.updateStatusUI('Requesting Location...');

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        // Get initial position
        navigator.geolocation.getCurrentPosition(
            (position) => {
                console.log('Initial position obtained:', position.coords);
                this.handleLocationUpdate(position);
                
                // Start watching position for real-time updates
                this.watchId = navigator.geolocation.watchPosition(
                    (position) => {
                        console.log('Position update from watch:', position.coords);
                        this.handleLocationUpdate(position);
                    },
                    (error) => this.handleLocationError(error),
                    options
                );
            },
            (error) => {
                console.error('Error getting initial position:', error);
                this.handleLocationError(error);
            },
            options
        );
        */
        // ============================================
    }

    /**
     * Stop tracking user location
     */
    stopTracking() {
        // Clear static location interval if in testing mode
        if (this.staticLocationInterval) {
            clearInterval(this.staticLocationInterval);
            this.staticLocationInterval = null;
        }
        
        // ============================================
        // REALTIME TRACKING CODE (COMMENTED OUT)
        // ============================================
        /*
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        */
        // ============================================

        this.isTracking = false;
        this.updateStatusUI('Location Tracking Stopped');

        // Optionally remove marker
        // if (this.userMarker) {
        //     this.userMarker.remove();
        //     this.userMarker = null;
        // }
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
            background: ${type === 'error' ? '#ef4444' : type === 'success' ? '#22c55e' : '#3b82f6'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 10001;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            max-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;
        
        // Add animation
        if (!document.getElementById('notification-animations')) {
            const animStyle = document.createElement('style');
            animStyle.id = 'notification-animations';
            animStyle.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(animStyle);
        }
        
        document.body.appendChild(notification);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            notification.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    /**
     * Get current position
     */
    getCurrentPosition() {
        return this.currentPosition;
    }

    /**
     * Center map on user location
     */
    centerOnUser() {
        if (this.currentPosition) {
            this.map.flyTo({
                center: [this.currentPosition.lng, this.currentPosition.lat],
                zoom: 18,
                duration: 1000
            });
        } else {
            this.showNotification('Location not available yet. Please wait...', 'error');
        }
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Will be initialized by the map page
    });
}

