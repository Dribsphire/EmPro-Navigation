// Real-time Office Synchronization
// Polls the API periodically to detect changes and update the map

class RealtimeOfficeSync {
    constructor(map, createMarkersCallback, updateNavigationTrackerCallback) {
        this.map = map;
        this.createMarkersCallback = createMarkersCallback;
        this.updateNavigationTrackerCallback = updateNavigationTrackerCallback;
        this.currentOffices = new Map(); // Store offices by office_id for quick lookup
        this.pollInterval = 15000; // Poll every 15 seconds
        this.pollTimer = null;
        this.isPolling = false;
    }

    /**
     * Start polling for office updates
     */
    start() {
        if (this.isPolling) {
            return; // Already polling
        }

        this.isPolling = true;
        console.log('Starting real-time office sync...');
        
        // Initial sync
        this.syncOffices();
        
        // Set up periodic polling
        this.pollTimer = setInterval(() => {
            this.syncOffices();
        }, this.pollInterval);
    }

    /**
     * Stop polling for office updates
     */
    stop() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
        this.isPolling = false;
        console.log('Stopped real-time office sync');
    }

    /**
     * Sync offices with database
     */
    async syncOffices() {
        try {
            const response = await fetch('../../api/get_offices_public.php');
            if (!response.ok) {
                console.error('Failed to fetch offices for sync');
                return;
            }

            const data = await response.json();
            if (data.status !== 'success' || !data.offices) {
                return;
            }

            const fetchedOffices = data.offices;
            const fetchedOfficesMap = new Map();
            
            // Create map of fetched offices by office_id
            fetchedOffices.forEach(office => {
                if (office.office_id) {
                    fetchedOfficesMap.set(office.office_id, office);
                }
            });

            // Detect new offices
            const newOffices = [];
            fetchedOffices.forEach(office => {
                if (office.office_id && !this.currentOffices.has(office.office_id)) {
                    newOffices.push(office);
                }
            });

            // Detect deleted offices
            const deletedOffices = [];
            this.currentOffices.forEach((office, officeId) => {
                if (!fetchedOfficesMap.has(officeId)) {
                    deletedOffices.push(office);
                }
            });

            // Detect updated offices (check if name, location, or description changed)
            const updatedOffices = [];
            fetchedOffices.forEach(fetchedOffice => {
                if (fetchedOffice.office_id) {
                    const currentOffice = this.currentOffices.get(fetchedOffice.office_id);
                    if (currentOffice) {
                        // Check if office was updated
                        if (
                            currentOffice.name !== fetchedOffice.name ||
                            JSON.stringify(currentOffice.lngLat) !== JSON.stringify(fetchedOffice.lngLat) ||
                            currentOffice.popup !== fetchedOffice.popup ||
                            JSON.stringify(currentOffice.gallery || []) !== JSON.stringify(fetchedOffice.gallery || [])
                        ) {
                            updatedOffices.push(fetchedOffice);
                        }
                    }
                }
            });

            // Update current offices map
            this.currentOffices = fetchedOfficesMap;

            // Handle changes
            if (newOffices.length > 0) {
                console.log(`Found ${newOffices.length} new office(s)`, newOffices);
                this.handleNewOffices(newOffices);
            }

            if (deletedOffices.length > 0) {
                console.log(`Found ${deletedOffices.length} deleted office(s)`, deletedOffices);
                this.handleDeletedOffices(deletedOffices);
            }

            if (updatedOffices.length > 0) {
                console.log(`Found ${updatedOffices.length} updated office(s)`, updatedOffices);
                this.handleUpdatedOffices(updatedOffices);
            }

            // Update navigation tracker with all current offices
            if (this.updateNavigationTrackerCallback && fetchedOffices.length > 0) {
                this.updateNavigationTrackerCallback(Array.from(fetchedOfficesMap.values()));
            }

        } catch (error) {
            console.error('Error syncing offices:', error);
        }
    }

    /**
     * Handle newly added offices
     */
    handleNewOffices(newOffices) {
        if (this.createMarkersCallback) {
            this.createMarkersCallback(newOffices);
        }
        
        // Show notification
        if (newOffices.length === 1) {
            this.showNotification(`New office added: ${newOffices[0].name}`, 'info');
        } else {
            this.showNotification(`${newOffices.length} new offices added`, 'info');
        }
    }

    /**
     * Handle deleted offices
     */
    handleDeletedOffices(deletedOffices) {
        deletedOffices.forEach(office => {
            // Remove marker from map
            if (office.markerInstance) {
                office.markerInstance.remove();
            }
            
            // Remove from allBuildingMarkers array (if it exists globally)
            if (typeof window !== 'undefined' && window.allBuildingMarkers) {
                const index = window.allBuildingMarkers.findIndex(b => 
                    b.office_id === office.office_id || b.name === office.name
                );
                if (index > -1) {
                    window.allBuildingMarkers.splice(index, 1);
                }
            }
            
            // Remove radius circle if navigation tracker exists
            if (window.navigationTracker && window.navigationTracker.radiusCircles) {
                const circleKey = office.office_id || office.name;
                const circle = window.navigationTracker.radiusCircles.get(circleKey);
                if (circle) {
                    if (this.map.getLayer(circle.layerId)) {
                        this.map.removeLayer(circle.layerId);
                    }
                    if (this.map.getSource(circle.sourceId)) {
                        this.map.removeSource(circle.sourceId);
                    }
                    window.navigationTracker.radiusCircles.delete(circleKey);
                }
            }
        });
        
        // Show notification
        if (deletedOffices.length === 1) {
            this.showNotification(`Office removed: ${deletedOffices[0].name}`, 'info');
        } else {
            this.showNotification(`${deletedOffices.length} offices removed`, 'info');
        }
    }

    /**
     * Handle updated offices
     */
    handleUpdatedOffices(updatedOffices) {
        updatedOffices.forEach(updatedOffice => {
            // Find existing marker
            if (typeof window !== 'undefined' && window.allBuildingMarkers) {
                const existingIndex = window.allBuildingMarkers.findIndex(b => 
                    b.office_id === updatedOffice.office_id
                );
                
                if (existingIndex > -1) {
                    const existing = window.allBuildingMarkers[existingIndex];
                    
                    // Remove old marker
                    if (existing.markerInstance) {
                        existing.markerInstance.remove();
                    }
                    
                    // Update the office data
                    Object.assign(existing, updatedOffice);
                    
                    // Recreate marker with updated data
                    if (this.createMarkersCallback) {
                        this.createMarkersCallback([updatedOffice]);
                    }
                    
                    // Update radius circle if location changed
                    if (JSON.stringify(existing.lngLat) !== JSON.stringify(updatedOffice.lngLat)) {
                        if (window.navigationTracker) {
                            const circleKey = updatedOffice.office_id || updatedOffice.name;
                            const circle = window.navigationTracker.radiusCircles.get(circleKey);
                            if (circle) {
                                // Remove old circle
                                if (this.map.getLayer(circle.layerId)) {
                                    this.map.removeLayer(circle.layerId);
                                }
                                if (this.map.getSource(circle.sourceId)) {
                                    this.map.removeSource(circle.sourceId);
                                }
                                
                                // Add new circle at new location
                                const radius = updatedOffice.radius || 50;
                                window.navigationTracker.addRadiusCircle(updatedOffice, radius);
                            }
                        }
                    }
                }
            }
        });
        
        // Show notification
        if (updatedOffices.length === 1) {
            this.showNotification(`Office updated: ${updatedOffices[0].name}`, 'info');
        } else {
            this.showNotification(`${updatedOffices.length} offices updated`, 'info');
        }
    }

    /**
     * Show notification to user
     */
    showNotification(message, type = 'info') {
        // Only show notifications for significant changes (new/deleted offices)
        // Skip notifications for updates to avoid spam
        if (type === 'info' && message.includes('updated')) {
            return; // Don't show update notifications
        }
        
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1002;
            font-family: Arial, sans-serif;
            font-size: 14px;
            max-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;
        
        // Add animation
        const style = document.createElement('style');
        style.textContent = `
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
        document.head.appendChild(style);
        
        document.body.appendChild(notification);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            notification.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => {
                notification.remove();
                style.remove();
            }, 300);
        }, 4000);
    }

    /**
     * Initialize with current offices
     */
    initialize(currentOffices) {
        currentOffices.forEach(office => {
            if (office.office_id) {
                this.currentOffices.set(office.office_id, office);
            }
        });
        console.log(`Initialized sync with ${this.currentOffices.size} offices`);
    }
}

