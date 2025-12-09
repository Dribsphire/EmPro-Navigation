/**
 * Drill Alert Popup System
 * This script checks for active drill alerts and displays a popup modal
 * on all student and guest pages. The popup persists until the admin ends the alert.
 * When dismissed, it automatically starts navigation to the safe zone (field).
 */

(function() {
    'use strict';

    // Configuration
    const CHECK_INTERVAL = 5000; // Check every 5 seconds
    const REAPPEAR_DELAY = 30000; // Show again after 30 seconds if dismissed but alert still active
    const API_ENDPOINT = '../../api/check_drill_alert.php';
    const SOUND_INTERVAL = 2000; // Play sound every 2 seconds while alert is active
    let lastAlertId = null; // Track last shown alert to avoid duplicate notifications
    let isDismissed = false; // Track if user dismissed the alert
    let dismissTimer = null; // Timer to show alert again after dismissal
    let soundInterval = null; // Interval for looping alert sound
    let audioContext = null; // Shared audio context for better performance

    // Initialize audio context (reuse for better performance)
    function getAudioContext() {
        if (!audioContext) {
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            } catch (error) {
                console.log('Could not create audio context:', error);
                return null;
            }
        }
        // Resume audio context if suspended (required for autoplay policies)
        if (audioContext.state === 'suspended') {
            audioContext.resume().catch(err => {
                console.log('Could not resume audio context:', err);
            });
        }
        return audioContext;
    }

    // Create alert sound using Web Audio API
    function playAlertSound() {
        const ctx = getAudioContext();
        if (!ctx) return;

        try {
            const oscillator = ctx.createOscillator();
            const gainNode = ctx.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(ctx.destination);

            // Create a beeping alarm sound pattern (more urgent)
            oscillator.frequency.setValueAtTime(800, ctx.currentTime);
            oscillator.frequency.setValueAtTime(600, ctx.currentTime + 0.1);
            oscillator.frequency.setValueAtTime(800, ctx.currentTime + 0.2);
            oscillator.frequency.setValueAtTime(600, ctx.currentTime + 0.3);
            oscillator.frequency.setValueAtTime(800, ctx.currentTime + 0.4);

            // Set volume (0.3 = 30% volume)
            gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);

            oscillator.start(ctx.currentTime);
            oscillator.stop(ctx.currentTime + 0.5);
        } catch (error) {
            console.log('Could not play alert sound:', error);
        }
    }

    // Start looping alert sound
    function startAlertSound() {
        // Stop any existing sound loop
        stopAlertSound();
        
        // Play sound immediately
        playAlertSound();
        
        // Then loop every SOUND_INTERVAL milliseconds
        soundInterval = setInterval(() => {
            playAlertSound();
        }, SOUND_INTERVAL);
    }

    // Stop looping alert sound
    function stopAlertSound() {
        if (soundInterval) {
            clearInterval(soundInterval);
            soundInterval = null;
        }
    }

    // Trigger vibration on mobile devices
    function triggerVibration() {
        if ('vibrate' in navigator) {
            try {
                // Pattern: vibrate for 200ms, pause 100ms, vibrate 200ms, pause 100ms, vibrate 200ms
                navigator.vibrate([200, 100, 200, 100, 200]);
            } catch (error) {
                console.log('Could not trigger vibration:', error);
            }
        }
    }

    // Start navigation to safe zone (field) when drill alert appears
    // This is called immediately when alert is shown, so route is ready when user clicks X
    function startNavigationToSafeZone() {
        // Safe zone coordinates from geojson (field center point)
        // Using the safe_zone point: [122.93940893612802, 10.643069956349777]
        // Format: [longitude, latitude] for Mapbox
        const safeZoneCoordinates = [122.93940893612802, 10.643069956349777];
        
        console.log('Drill alert active - starting navigation to safe zone immediately');
        
        // Start navigation immediately (no delay)
        // Check if NavigationTracker is available
        if (window.navigationTracker && typeof window.navigationTracker.startNavigation === 'function') {
            // Try to find "Field" or "Safe Zone" office in the offices list
            // First, try to get it from allBuildingMarkers or offices
            let safeZoneOffice = null;
            
            // Check if there's a "Field" office in the offices
            if (window.navigationTracker.offices) {
                safeZoneOffice = window.navigationTracker.offices.find(office => 
                    office.name && (
                        office.name.toLowerCase().includes('field') || 
                        office.name.toLowerCase().includes('safe zone') ||
                        office.name.toLowerCase().includes('evacuation')
                    )
                );
            }
            
            // If not found in offices, check allBuildingMarkers
            if (!safeZoneOffice && window.allBuildingMarkers) {
                safeZoneOffice = window.allBuildingMarkers.find(building => 
                    building.name && (
                        building.name.toLowerCase().includes('field') || 
                        building.name.toLowerCase().includes('safe zone') ||
                        building.name.toLowerCase().includes('evacuation')
                    )
                );
            }
            
            // If still not found, create a temporary office object for navigation
            if (!safeZoneOffice) {
                safeZoneOffice = {
                    name: 'Safe Zone (Field)',
                    office_id: null, // Will try to find by name in API
                    lngLat: safeZoneCoordinates,
                    radius: 50, // Default radius in meters
                    description: 'Emergency evacuation safe zone'
                };
            }
            
            console.log('Starting navigation to safe zone:', safeZoneOffice);
            
            try {
                // Start navigation immediately - route will be calculated in background
                // The API will try to find office by name if office_id is null
                window.navigationTracker.startNavigation(safeZoneOffice).catch(error => {
                    console.warn('Navigation logging failed, but continuing with route display:', error);
                    // Even if logging fails, still show the route
                    displayRouteToSafeZone(safeZoneCoordinates);
                });
                console.log('Navigation to safe zone started - route is being calculated');
            } catch (error) {
                console.error('Error starting navigation to safe zone:', error);
                // Fallback: display route without logging
                displayRouteToSafeZone(safeZoneCoordinates);
            }
        } else {
            console.warn('NavigationTracker not available, displaying route to safe zone');
            // Fallback: display route without full navigation tracking
            displayRouteToSafeZone(safeZoneCoordinates);
        }
    }
    
    // Helper function to display route to safe zone without full navigation logging
    function displayRouteToSafeZone(coordinates) {
        // Try to find map instance
        let mapInstance = null;
        
        // Check global map variable
        if (typeof map !== 'undefined' && map) {
            mapInstance = map;
        } else if (window.map) {
            mapInstance = window.map;
        } else if (window.navigationTracker && window.navigationTracker.map) {
            mapInstance = window.navigationTracker.map;
        }
        
        if (mapInstance && typeof mapInstance.flyTo === 'function') {
            // Center map on safe zone
            mapInstance.flyTo({
                center: coordinates,
                zoom: 18,
                duration: 2000
            });
            
            // Try to display route if route finder is available
            if (window.navigationTracker && window.navigationTracker.routeFinder) {
                // Get user's current location
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const userLat = position.coords.latitude;
                            const userLng = position.coords.longitude;
                            const [safeLng, safeLat] = coordinates;
                            
                            // Calculate and display route
                            window.navigationTracker.routeFinder.updateRoute(
                                userLng, 
                                userLat, 
                                safeLng, 
                                safeLat
                            ).catch(err => {
                                console.warn('Could not display route to safe zone:', err);
                            });
                        },
                        (error) => {
                            console.warn('Could not get user location for route:', error);
                        }
                    );
                }
            }
            
            console.log('Map centered on safe zone and route displayed');
        } else {
            console.error('Map not available for navigation to safe zone');
        }
    }

    // Create and inject the alert modal HTML
    function createAlertModal() {
        const modalHTML = `
            <div id="drillAlertPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 10000; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease-in;">
                <div style="background: #cc0000; color: white; padding: 2rem; border-radius: 16px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3); text-align: center; position: relative; animation: slideDown 0.4s ease-out;">
                    <button id="closeAlertBtn" style="position: absolute; top: 1rem; right: 1rem; background: rgba(255, 255, 255, 0.2); border: none; color: white; font-size: 1.5rem; width: 2.5rem; height: 2.5rem; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; font-weight: bold;" title="Close Alert">
                        &times;
                    </button>
                    <div style="font-size: 8rem; margin-bottom: 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#e3e3e3"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-114 59.5-210.5T301-838q1 19 4 38.5t10 45.5q-72 44-113.5 116.5T160-480q0 134 93 227t227 93q134 0 227-93t93-227q0-85-41.5-158T644-755q7-26 10-45.5t5-37.5q102 51 161.5 147T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-160q-100 0-170-70t-70-170q0-58 25.5-109t72.5-85q5 15 11 34.5t16 48.5q-22 23-33.5 51T320-480q0 66 47 113t113 47q66 0 113-47t47-113q0-32-11.5-60T595-591q8-24 14.5-44.5T621-674q47 34 73 85t26 109q0 100-70 170t-170 70Zm-40-380q-37-112-48.5-157.5T380-860q0-42 29-71t71-29q42 0 71 29t29 71q0 37-11.5 82.5T520-620h-80Zm40 220q-33 0-56.5-23.5T400-480q0-33 23.5-56.5T480-560q33 0 56.5 23.5T560-480q0 33-23.5 56.5T480-400Z"/></svg>
                    </div>
                    <h2 id="alertTitle" style="margin: 0 0 1rem 0; font-size: 1.8rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">DRILL ALERT</h2>
                    <div id="alertType" style="font-size: 1.2rem; margin-bottom: 1rem; font-weight: 600;"></div>
                    <div id="alertDescription" style="font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem; background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 8px;"></div>
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                        This alert will remain active until ended by an administrator.
                    </div>
                </div>
            </div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideDown {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            #drillAlertPopup {
                animation: fadeIn 0.3s ease-in;
            }
            #drillAlertPopup > div {
                animation: slideDown 0.4s ease-out, pulse 2s infinite;
            }
            #closeAlertBtn:hover {
                background: rgba(255, 255, 255, 0.3) !important;
                transform: scale(1.1);
            }
            #closeAlertBtn:active {
                transform: scale(0.95);
            }
        `;
        document.head.appendChild(style);

        // Inject modal into body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Add close button event listener
        const closeBtn = document.getElementById('closeAlertBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent modal click handler from firing
                const modal = document.getElementById('drillAlertPopup');
                if (modal) {
                    modal.style.display = 'none';
                    isDismissed = true;
                    // DO NOT stop sound - it should continue even if modal is dismissed
                    // Sound will only stop when admin ends the alert
                    // Navigation to safe zone is already running (started when alert appeared)
                    // Just hide the modal - route should already be visible
                    
                    // Set timer to show again after delay if alert is still active
                    if (dismissTimer) {
                        clearTimeout(dismissTimer);
                    }
                    dismissTimer = setTimeout(function() {
                        isDismissed = false; // Allow alert to show again
                        checkForAlerts(); // Check immediately
                    }, REAPPEAR_DELAY);
                }
            });
        }

        // Also allow closing by clicking outside the modal
        const modal = document.getElementById('drillAlertPopup');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    isDismissed = true;
                    // DO NOT stop sound - it should continue even if modal is dismissed
                    // Sound will only stop when admin ends the alert
                    // Navigation to safe zone is already running (started when alert appeared)
                    // Just hide the modal - route should already be visible
                    
                    // Set timer to show again after delay if alert is still active
                    if (dismissTimer) {
                        clearTimeout(dismissTimer);
                    }
                    dismissTimer = setTimeout(function() {
                        isDismissed = false; // Allow alert to show again
                        checkForAlerts(); // Check immediately
                    }, REAPPEAR_DELAY);
                }
            });
        }
    }

    // Show the alert popup
    function showAlert(alert, isNewAlert = false) {
        const modal = document.getElementById('drillAlertPopup');
        if (!modal) {
            createAlertModal();
            return showAlert(alert, isNewAlert);
        }

        const titleEl = document.getElementById('alertTitle');
        const typeEl = document.getElementById('alertType');
        const descEl = document.getElementById('alertDescription');

        if (titleEl) titleEl.textContent = alert.title || 'DRILL ALERT';
        if (typeEl) typeEl.textContent = `Type: ${alert.alert_type ? alert.alert_type.charAt(0).toUpperCase() + alert.alert_type.slice(1) : 'Emergency'}`;
        if (descEl) descEl.textContent = alert.description || 'Please follow safety procedures.';

        // Only show if not dismissed or if it's a new alert
        if (!isDismissed || isNewAlert) {
            modal.style.display = 'flex';
            isDismissed = false; // Reset dismissal status for new alerts

            // Start/continue looping sound whenever alert is active
            // Sound should continue even if user dismisses the modal
            if (isNewAlert) {
                startAlertSound();
                triggerVibration();
                
                // Automatically start navigation to safe zone when alert appears
                // This ensures route is calculated immediately
                console.log('New drill alert detected - starting navigation to safe zone immediately');
                startNavigationToSafeZone();
            } else {
                // Ensure sound continues if it was stopped
                if (!soundInterval) {
                    startAlertSound();
                }
            }
        } else {
            // Even if modal is dismissed, keep sound playing if alert is still active
            if (!soundInterval) {
                startAlertSound();
            }
        }
    }

    // Hide the alert popup
    function hideAlert() {
        const modal = document.getElementById('drillAlertPopup');
        if (modal) {
            modal.style.display = 'none';
        }
        // Note: Sound is NOT stopped here - it should only stop when admin ends the alert
        // Sound stopping is handled in checkForAlerts() when no alert is found
    }

    // Check for active alerts
    async function checkForAlerts() {
        try {
            const response = await fetch(API_ENDPOINT, {
                method: 'GET',
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            });

            if (!response.ok) {
                console.error('Failed to check for alerts:', response.status);
                return;
            }

            const data = await response.json();

            if (data.status === 'success') {
                if (data.has_alert && data.alert) {
                    // Check if this is a new alert (different ID)
                    const isNewAlert = lastAlertId !== data.alert.alert_id;
                    
                    // If alert ID changed, reset dismissal status
                    if (isNewAlert) {
                        isDismissed = false;
                    }
                    
                    lastAlertId = data.alert.alert_id;
                    
                    // Show alert if not dismissed, or if it's a new alert
                    if (!isDismissed) {
                        showAlert(data.alert, isNewAlert);
                    } else {
                        // Alert is dismissed but still active - ensure sound continues
                        if (!soundInterval) {
                            startAlertSound();
                        }
                    }
                } else {
                    // No active alert - admin ended it
                    lastAlertId = null;
                    isDismissed = false; // Reset dismissal when alert ends
                    if (dismissTimer) {
                        clearTimeout(dismissTimer);
                        dismissTimer = null;
                    }
                    // Stop sound when alert is ended by admin
                    stopAlertSound();
                    hideAlert();
                }
            }
        } catch (error) {
            console.error('Error checking for drill alerts:', error);
        }
    }

    // Initialize on page load
    function init() {
        // Create modal if it doesn't exist
        if (!document.getElementById('drillAlertPopup')) {
            createAlertModal();
        }

        // Check immediately
        checkForAlerts();

        // Set up polling interval
        setInterval(checkForAlerts, CHECK_INTERVAL);
    }

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also check when page becomes visible (user switches tabs back)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            checkForAlerts();
        }
    });

})();

