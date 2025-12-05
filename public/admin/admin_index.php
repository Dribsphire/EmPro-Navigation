
<?php
require_once __DIR__ . '/../../services/Auth.php';
$auth = new Auth();
$auth->requireAdmin();
$currentAdmin = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>EmPro</title>
<meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no">
<link rel="icon" type="image/png" href="../images/CHMSU.png">
<link href="https://api.mapbox.com/mapbox-gl-js/v3.17.0-beta.1/mapbox-gl.css" rel="stylesheet">
<link rel="stylesheet" href="../css/admin_Style.css">
<script type="text/javascript" src="../script/app.js" defer></script>
<script src="https://api.mapbox.com/mapbox-gl-js/v3.17.0-beta.1/mapbox-gl.js"></script>
<script src="../script/admin_map.js?v=<?= time(); ?>"></script>
<style>
/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow: auto;
}

.modal-content {
    background-color: #11121a;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-top: 5rem;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.close-modal {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: #000;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    background-color: var(--base-clr);
    color: var(--text-clr);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn-submit, .btn-cancel {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-submit {
    background-color: #4CAF50;
    color: white;
}

.btn-submit:hover {
    background-color: #45a049;
}

.btn-cancel {
    background-color: #f44336;
    color: white;
}

.btn-cancel:hover {
    background-color: #d32f2f;
}

.image-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.preview-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.existing-image-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
}

.existing-image-item .image-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.existing-image-item .image-label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}

.delete-image-btn {
    padding: 5px 10px;
    background-color: #f44336;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: background-color 0.2s;
}

.delete-image-btn:hover {
    background-color: #d32f2f;
}

/* Original styles */
body { margin: 0; padding: 0; }
#map { position: absolute; top: 5px; bottom: 25px; width: 98% !important; border-radius:20px; margin-left:10px;}
.marker {
        display: block;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        padding: 0;
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
    }
.mapboxgl-popup {
        max-width: 200px;
        color: #000 !important;
        padding: 10px;
        border-radius: 5px;
        font-weight: bold;
        font-family: 'Poppins', sans-serif;
        
        }
@media (max-width: 768px) {
        #map { width: 100%; bottom: 65px; border-radius:0px;
            
    }
    .modal{
        display:none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 63%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow: auto;
        margin-top: 9rem;
        border-radius: 24px;
    }
    

}
</style>
</head>
<body>
<?php include 'admin_nav.php'; ?>   
<div class="container"> 
<div class="map_buttons">
    <div class="map-search">
        
        <label class="sr-only" for="building-search">Search buildings</label>
        <input id="building-search" list="building-list" placeholder="Search buildings..." autocomplete="off">
        <button type="button" id="add-office" aria-pressed="false">Add Office</button>
        <button type="button" id="drill_alert" aria-pressed="false">Drill Alert</button>
        <button type="button" id="toggle-3d" aria-pressed="false">3D View</button>
        
        
       
        <datalist id="building-list">
            <option value="RDCAGIS">
            <option value="CCS office">
            <option value="Canteen">
            <option value="Library">
            <option value="Registrar Office">
            <option value="OSA">
            <option value="Cashier Office">
            <option value="Audio Visual Room (AVR)">
            <option value="COE Office">
            <option value="COED Office">
            <option value="CIT Office">
            <option value="Technopacer Office">
            <option value="Student Government Office (CUSG)">
            <option value="Clinic">
        </datalist>
    </div>
</div>
    <div id="map"></div>
    
    <!-- Drill Alert Modal -->
    <div id="drillAlertModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Drill Alert Control</h2>
                <span class="close-modal">&times;</span>
            </div>
            
            <!-- Active Alert Status -->
            <div id="activeAlertStatus" style="display: none; background: #ffe0e0; border: 1px solid #ff4444; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <strong style="color: #cc0000;">Active Alert:</strong>
                        <span id="activeAlertTitle" style="color: #cc0000; font-weight: 600;"></span>
                    </div>
                    <button type="button" id="endAlertBtn" style="background: #cc0000; color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background 0.3s ease; white-space: nowrap;" onmouseover="this.style.background='#aa0000'" onmouseout="this.style.background='#cc0000'">
                        End Alert
                    </button>
                </div>
            </div>
            
            <form id="drillAlertForm">
                <div class="form-group">
                    <label for="alertType">Type of Alert:</label>
                    <select id="alertType" name="alertType" required>
                        <option value="">Select alert type</option>
                        <option value="fire"> Fire Drill</option>
                        <option value="earthquake"> Earthquake Drill</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="alertDescription">Description/Instructions:</label>
                    <textarea id="alertDescription" name="alertDescription" rows="4" required placeholder="Enter evacuation instructions or safety guidelines..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-submit"> Send Alert</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../script/showlayout.js"></script>
<script>
    // Suppress Mapbox console warnings and errors
    (function() {
        const originalError = console.error;
        const originalWarn = console.warn;
        
        // Filter out Mapbox-specific warnings and errors
        console.error = function(...args) {
            const message = args.join(' ');
            // Suppress Mapbox featureNamespace warnings
            if (message.includes('featureNamespace') && message.includes('featureset')) {
                return;
            }
            // Suppress blocked client errors (ad blockers)
            if (message.includes('ERR_BLOCKED_BY_CLIENT') || message.includes('events.mapbox.com')) {
                return;
            }
            originalError.apply(console, args);
        };
        
        console.warn = function(...args) {
            const message = args.join(' ');
            // Suppress Mapbox terrain/hillshade warnings
            if (message.includes('Terrain and hillshade are disabled') || 
                message.includes('Canvas2D limitations') ||
                message.includes('fingerprinting protection')) {
                return;
            }
            // Suppress featureNamespace warnings
            if (message.includes('featureNamespace') && message.includes('featureset')) {
                return;
            }
            originalWarn.apply(console, args);
        };
    })();
</script>
<script>
    // Drill Alert Modal Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('drillAlertModal');
        const btn = document.getElementById('drill_alert');
        const closeBtn = modal.querySelector('.close-modal');
        const cancelBtn = modal.querySelector('.btn-cancel');
        const form = document.getElementById('drillAlertForm');
        const activeAlertStatus = document.getElementById('activeAlertStatus');
        const activeAlertTitle = document.getElementById('activeAlertTitle');
        const endAlertBtn = document.getElementById('endAlertBtn');
        
        let currentActiveAlertId = null;

        // Check for active alerts
        async function checkActiveAlert() {
            try {
                const response = await fetch('../../api/check_drill_alert.php');
                const data = await response.json();
                
                if (data.status === 'success' && data.has_alert && data.alert) {
                    currentActiveAlertId = data.alert.alert_id;
                    activeAlertTitle.textContent = data.alert.title;
                    activeAlertStatus.style.display = 'block';
                    endAlertBtn.disabled = false;
                    endAlertBtn.textContent = 'End Alert';
                    endAlertBtn.style.cursor = 'pointer';
                    endAlertBtn.style.opacity = '1';
                } else {
                    currentActiveAlertId = null;
                    activeAlertStatus.style.display = 'none';
                    endAlertBtn.disabled = true;
                }
            } catch (error) {
                console.error('Error checking active alert:', error);
            }
        }

        // Open modal when button is clicked
        btn.onclick = function() {
            modal.style.display = 'block';
            checkActiveAlert();
        }

        // Close modal when X is clicked
        closeBtn.onclick = function() {
            modal.style.display = 'none';
            form.reset();
        }

        // Close modal when Cancel button is clicked
        cancelBtn.onclick = function() {
            modal.style.display = 'none';
            form.reset();
        }

        // Close modal when clicking outside the modal content
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
                form.reset();
            }
        }
        
        // End active alert
        endAlertBtn.onclick = async function() {
            if (!currentActiveAlertId) {
                alert('❌ No active alert to end.');
                return;
            }
            
            if (!confirm('Are you sure you want to end the active drill alert?\n\nThis will stop the alert for all students.')) {
                return;
            }
            
            endAlertBtn.disabled = true;
            endAlertBtn.textContent = 'Ending...';
            
            try {
                const response = await fetch('../../api/end_drill_alert.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        alert_id: currentActiveAlertId
                    })
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('✅ Drill alert has been ended successfully.\n\nAll students will no longer see the alert.');
                    activeAlertStatus.style.display = 'none';
                    currentActiveAlertId = null;
                    // Refresh the alert status
                    checkActiveAlert();
                } else {
                    alert('❌ Error: ' + (result.message || 'Failed to end alert'));
                    endAlertBtn.disabled = false;
                    endAlertBtn.textContent = 'End Alert';
                }
            } catch (error) {
                console.error('Error ending drill alert:', error);
                alert('❌ Error: Failed to end drill alert. Please try again.');
                endAlertBtn.disabled = false;
                endAlertBtn.textContent = 'End Alert';
            }
        };

        // Handle form submission
        form.onsubmit = async function(e) {
            e.preventDefault();
            const alertType = document.getElementById('alertType').value;
            const alertDescription = document.getElementById('alertDescription').value;
            const submitBtn = form.querySelector('.btn-submit');
            
            // Disable button while sending
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            
            try {
                const response = await fetch('../../api/send_drill_alert.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        alert_type: alertType,
                        description: alertDescription
                    })
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('✅ Drill alert has been sent to all students!');
                    modal.style.display = 'none';
                    form.reset();
                    // Refresh active alert status to show the new alert
                    setTimeout(() => {
                        checkActiveAlert();
                    }, 500);
                } else {
                    alert('❌ Error: ' + (result.message || 'Failed to send alert'));
                }
            } catch (error) {
                console.error('Error sending drill alert:', error);
                alert('❌ Error: Failed to send drill alert. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '📢 Send Alert';
            }
        };
    });

    mapboxgl.accessToken = 'pk.eyJ1IjoiZHJpYnNwaGlyZSIsImEiOiJjbWllamJrdzEwM2ZrM3FwczFyY2h5cGRwIn0.SdWyL8hhdYbwMvEQ6wsaAQ';
    const mediaQuery = window.matchMedia('(max-width: 768px)');
    let isMobileViewport = mediaQuery.matches;

    const VIEW_2D_DESKTOP = {
        center: [122.93922, 10.64276],
        zoom: 17.35,
        bearing: -153.31,
        pitch: 0
    };
    const VIEW_3D_DESKTOP = {
        center: [122.93957, 10.64291],
        zoom: 17.8,
        bearing: -150.11,
        pitch: 47.51
    };
    const VIEW_2D_MOBILE = {
        center: [122.9399, 10.64303],
        zoom: 18.08,
        bearing: -150.9,
        pitch: 0
    };
    const VIEW_3D_MOBILE = {
        center: [122.9399, 10.64303],
        zoom: 18.08,
        bearing: -150.9,
        pitch: 54
    };

    const getViewPreset = (mode) => {
        if (mode === '3d') {
            return isMobileViewport ? VIEW_3D_MOBILE : VIEW_3D_DESKTOP;
        }
        return isMobileViewport ? VIEW_2D_MOBILE : VIEW_2D_DESKTOP;
    };

    const map = new mapboxgl.Map({
        container: 'map', // container ID
        style: 'mapbox://styles/mapbox/standard', // base map style
        ...getViewPreset('2d'),
        // Disable telemetry to reduce blocked request errors
        collectResourceTiming: false
    });

    // Check if map is already loaded (sometimes it loads very quickly)
    if (map.loaded()) {
        console.log('Map already loaded, loading offices immediately');
        setTimeout(() => loadOfficesFromDatabase(), 500);
    }

    const toggle3dBtn = document.getElementById('toggle-3d');
    let is3DEnabled = false;

    const update3DState = (enable) => {
        is3DEnabled = enable;
        if (!toggle3dBtn) {
            return;
        }
        toggle3dBtn.textContent = enable ? '2D View' : '3D View';
        toggle3dBtn.setAttribute('aria-pressed', String(is3DEnabled));
        map.flyTo({
            ...getViewPreset(enable ? '3d' : '2d'),
            essential: true,
            duration: 900
        });
    };

    if (toggle3dBtn) {
        toggle3dBtn.addEventListener('click', () => update3DState(!is3DEnabled));
    }

    mediaQuery.addEventListener('change', (event) => {
        isMobileViewport = event.matches;
        map.flyTo({
            ...getViewPreset(is3DEnabled ? '3d' : '2d'),
            essential: true,
            duration: 500
        });
    });

    // Global array to store all markers for search functionality
    let allBuildingMarkers = [];

    // Function to create markers from building data
    function createMarkers(buildings) {
        if (!buildings || buildings.length === 0) {
            console.log('No buildings to create markers for');
            return;
        }
        
        console.log(`Creating ${buildings.length} markers...`);
        
        buildings.forEach((building) => {
            
        const { lngLat, image, popup, iconSize, name } = building;
        const gallery = building.gallery ?? [];
        
        if (!lngLat || !Array.isArray(lngLat) || lngLat.length !== 2) {
            console.error('Invalid lngLat for building:', name, lngLat);
            return;
        }
        
        console.log(`Creating marker for: ${name} at [${lngLat[0]}, ${lngLat[1]}]`);
        
        // Determine image path - if it starts with 'buildings/' or 'building_content/', use as is
        // Otherwise, assume it's in the buildings folder
        let imagePath = image;
        if (!image.startsWith('buildings/') && !image.startsWith('building_content/') && !image.startsWith('../')) {
            imagePath = `../../buildings/${image}`;
        } else if (image.startsWith('buildings/')) {
            imagePath = `../../${image}`;
        } else if (image.startsWith('building_content/')) {
            imagePath = `../../${image}`;
        }
        
        const el = document.createElement('div');
        const width = iconSize[0];
        const height = iconSize[1];
        el.className = 'marker';
        el.style.backgroundImage = `url(${imagePath})`;
        el.style.width = `${width}px`;
        el.style.height = `${height}px`;
        el.style.backgroundSize = 'cover';
        el.tabIndex = 0;
        el.ariaLabel = `Marker for ${name}`;

        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                window.alert(popup);
            }
        });

        // Format gallery paths
        const formattedGallery = gallery.map(img => {
            if (img.startsWith('building_content/')) {
                return `../../${img}`;
            }
            return img.startsWith('../') ? img : `../../${img}`;
        });

        const popupHTML = `
            <div style="padding: 8px 0; min-width: 200px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 16px; color: #333;">${name}</h3>
                    <button class="edit-office-btn" data-building="${name}" data-office-id="${building.office_id || ''}" style="
                        background: none;
                        border: none;
                        color: #2196F3;
                        cursor: pointer;
                        font-size: 14px;
                        padding: 2px 6px;
                        border-radius: 3px;
                    ">
                        Edit
                    </button>
                </div>
                <p class="office-description" style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
                    ${popup || building.description || 'No description available.'}
                </p>
                <div style="display: flex; gap: 8px; flex-direction: column;">
                    <button class="delete-office-btn" data-building="${name}" data-office-id="${building.office_id || ''}" style="
                        width: 100%;
                        padding: 6px 12px;
                        background-color: #f44336;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                        transition: background-color 0.2s;
                    ">
                        Delete Office
                    </button>
                    ${formattedGallery.length > 0 ? `
                    <button class="view-content-btn" data-gallery='${JSON.stringify(formattedGallery)}' style="
                        width: 100%;
                        padding: 6px 12px;
                        background-color: #2196F3;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                        transition: background-color 0.2s;
                    ">
                        View Content
                    </button>` : ''}
                </div>
            </div>
        `;
        const markerPopup = new mapboxgl.Popup({ offset: 25 }).setHTML(popupHTML);
        
        markerPopup.on('open', () => {
            const contentBtn = markerPopup._content.querySelector('.view-content-btn');
            const editBtn = markerPopup._content.querySelector('.edit-office-btn');
            const deleteBtn = markerPopup._content.querySelector('.delete-office-btn');
            
            if (contentBtn) {
                contentBtn.addEventListener('click', () => {
                    try {
                        const gallery = JSON.parse(contentBtn.dataset.gallery);
                        if (gallery && gallery.length > 0) {
                            showBuildingLayout(building.name, gallery);
                        }
                    } catch (e) {
                        console.error('Error parsing gallery data:', e);
                    }
                });
            }
            
            if (editBtn) {
                editBtn.addEventListener('click', () => {
                    markerPopup.remove();
                    const officeId = editBtn.dataset.officeId;
                    const buildingToEdit = allBuildingMarkers.find(b => b.office_id == officeId || b.name === editBtn.dataset.building);
                    if (buildingToEdit) {
                        showEditOfficeForm(buildingToEdit);
                    } else {
                        console.error('Building not found for editing');
                    }
                });
            }
            
            if (deleteBtn) {
                deleteBtn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    e.preventDefault();
                    
                    const buildingToDelete = allBuildingMarkers.find(b => b.name === deleteBtn.dataset.building);
                    if (!buildingToDelete) {
                        console.error('Building not found');
                        return;
                    }
                    
                    const officeId = buildingToDelete.office_id || deleteBtn.dataset.officeId;
                    if (!officeId) {
                        alert('Cannot delete: Office ID not found.');
                        return;
                    }
                    
                    // Single confirmation
                    if (!confirm(`Are you sure you want to delete "${buildingToDelete.name}"? This action cannot be undone.`)) {
                        return;
                    }
                    
                    // Close popup immediately
                    markerPopup.remove();
                    
                    try {
                        const formData = new FormData();
                        formData.append('office_id', officeId);
                        
                        const response = await fetch('../../api/delete_office.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.status === 'success') {
                            // Remove marker from map
                            if (buildingToDelete.markerInstance) {
                                buildingToDelete.markerInstance.remove();
                            }
                            
                            // Remove from array
                            const index = allBuildingMarkers.indexOf(buildingToDelete);
                            if (index > -1) {
                                allBuildingMarkers.splice(index, 1);
                            }
                            
                            // Reload page to refresh the map
                            window.location.reload();
                        } else {
                            alert('Error: ' + (result.message || 'Failed to delete office.'));
                        }
                    } catch (error) {
                        console.error('Error deleting office:', error);
                        alert('Error: Failed to delete office. Please try again.');
                    }
                });
            }
        });
        
        const markerInstance = new mapboxgl.Marker(el)
            .setLngLat(lngLat)
            .setPopup(markerPopup)
            .addTo(map);

        building.markerInstance = markerInstance;
        building.markerPopup = markerPopup;
        allBuildingMarkers.push(building);
        });
        
        console.log(`Successfully created ${buildings.length} markers. Total markers: ${allBuildingMarkers.length}`);
    }

    // Load offices from database
    async function loadOfficesFromDatabase() {
        try {
            console.log('Fetching offices from API...');
            const response = await fetch('../../api/get_offices.php');
            console.log('API Response status:', response.status);
            
            const responseText = await response.text();
            console.log('API Response text (first 200 chars):', responseText.substring(0, 200));
            
            if (response.ok) {
                try {
                    const data = JSON.parse(responseText);
                    console.log('API Response data:', data);
                    
                    if (data.status === 'success' && data.offices && data.offices.length > 0) {
                        console.log(`Loaded ${data.offices.length} offices from database:`, data.offices);
                        createMarkers(data.offices);
                    } else {
                        console.log('No offices found in database or empty array', data);
                    }
                } catch (parseError) {
                    console.error('Failed to parse JSON response:', parseError);
                    console.error('Response was:', responseText);
                }
            } else {
                console.error('Failed to load offices:', response.status, responseText);
            }
        } catch (error) {
            console.error('Error loading offices:', error);
        }
    }

    const searchInput = document.getElementById('building-search');
    let activePopup;

    const handleSearchSelection = () => {
        if (!searchInput) {
            return;
        }
        const query = searchInput.value.trim().toLowerCase();
        if (!query) {
            return;
        }

        const match = allBuildingMarkers.find((building) =>
            building.name.toLowerCase().includes(query)
        );

        if (!match) {
            return;
        }

        map.flyTo({
            center: match.lngLat,
            zoom: 17,
            essential: true
        });

        if (activePopup && activePopup !== match.markerPopup) {
            activePopup.remove();
        }

        match.markerPopup?.addTo(map);
        activePopup = match.markerPopup;
    };

    if (searchInput) {
        searchInput.addEventListener('change', handleSearchSelection);
        searchInput.addEventListener('search', handleSearchSelection);
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleSearchSelection();
            }
        });
    }
    // Initialize the map click handler after the map is loaded
    initMapClickHandler(map);
    
    // Load offices and setup map layers when map is ready
    map.on('load',()=>{
        // Load offices from database
        loadOfficesFromDatabase();
        
        //set the default atmosphere style
        //set the default atmosphere style
        map.setFog({});
        //add a source layer for the school boundaries 
        map.addSource('chmsu', {
        type: 'geojson',
        data: '../geojson/chmsu.geojson'
        });
        
        map.addLayer({
            id: 'chmsu-line-school',
            type: 'line',
            source: 'chmsu',
            filter: ['has', 'school'],
            paint:{
                'line-color':'orange',
                'line-width': 3
            }
        });

        map.addLayer({
            id:'chmsu-line-field',
            type: 'line',
            source:'chmsu',
            filter: ['has', 'field'],
            paint:{
                'line-color':'green',
                'line-width': 3
            }
        });

        map.addLayer({
            id:'chmsu-line-stgb',
            type:'line',
            source:'chmsu',
            filter:['has','stgb'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });

        map.addLayer({
            id:'chmsu-line-lab',
            type:'line',
            source:'chmsu',
            filter:['has','labBuilding'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-guardhouse',
            type:'line',
            source:'chmsu',
            filter:['has','guardhouse'],
            paint:{
                'line-color':'purple',
                'line-width': 2
            }
        });
        map.addLayer({
            id:'chmsu-line-academicBuilding',
            type:'line',
            source:'chmsu',
            filter:['has','academicBuilding'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-academicBuilding2',
            type:'line',
            source:'chmsu',
            filter:['has','academicBuilding2'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-academicBuilding3',
            type:'line',
            source:'chmsu',
            filter:['has','academicBuilding3'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-mechanicalBuilding',
            type:'line',
            source:'chmsu',
            filter:['has','mechanicalBuilding'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-automotiveBuilding',
            type:'line',
            source:'chmsu',
            filter:['has','automotiveBuilding'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-gymnasium',
            type:'line',
            source:'chmsu',
            filter:['has','gymnasium'],
            paint:{
                'line-color':'black',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-tvepBuilding',
            type:'line',
            source:'chmsu',
            filter:['has','tvepBuilding'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-avr',
            type:'line',
            source:'chmsu',
            filter:['has','avr'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-cflrg',
            type:'line',
            source:'chmsu',
            filter:['has','cflrg'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-USG/technoBuilding',
            type:'line',
            source:'chmsu',
            filter:['has','USG/technoBuilding'],
            paint:{
                'line-color':'blue',
                'line-width': 1
            }
        });

        map.addLayer({
            id:'chmsu-line-garden',
            type:'line',
            source:'chmsu',
            filter:['has','garden'],
            paint:{
                'line-color':'#0f451d',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-stockroom',
            type:'line',
            source:'chmsu',
            filter:['has','stockroom'],
            paint:{
                'line-color':'#0f451d',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-supplyoff',
            type:'line',
            source:'chmsu',
            filter:['has','supplyoff'],
            paint:{
                'line-color':'#0f451d',
                'line-width': 1
            }
        });
        map.addLayer({
            id:'chmsu-line-cr',
            type:'line',
            source:'chmsu',
            filter:['has','cr'],
            paint:{
                'line-color':'#16939e',
                'line-width': 1
            }
        });

        map.addLayer({
            id:'chmsu-line-road',
            type:'line',
            source:'chmsu',
            filter:['has','road'],
            paint:{
                'line-color':'red',
                'line-width': 2,
                'line-dasharray':[4,4]
            }
        });
        map.addLayer({
            id:'chmsu-line-footwalk',
            type:'line',
            source:'chmsu',
            filter:['has','footwalk'],
            paint:{
                'line-color':'black',
                'line-width': 2,
                'line-dasharray':[2,2]
            }
        });
    });
    
                //'line-dasharray': [2, 3]

    // Handle Add Office button click
    document.getElementById('add-office').addEventListener('click', function() {
        const addButton = this;
        // Disable the button to prevent multiple clicks
        addButton.disabled = true;
        addButton.textContent = 'Click for location';
        
        // Change cursor to pointer to indicate clickable area
        document.body.style.cursor = 'pointer';
        map.getCanvas().style.cursor = 'pointer';
        
        // Store the original click handler
        const originalClickHandlers = [...(map._listeners.click || [])];
        
        // Remove all click handlers
        map.off('click');
        
        // Add a one-time click handler for location selection
        map.once('click', function handleMapClick(e) {
            // Restore the original click handlers
            map.off('click');
            originalClickHandlers.forEach(handler => {
                map.on('click', handler.listener);
            });
            
            // Reset the button and cursor
            addButton.disabled = false;
            addButton.textContent = 'Add Office';
            document.body.style.cursor = '';
            map.getCanvas().style.cursor = '';
            
            // Show the office form with the selected coordinates
            if (window.showOfficeForm) {
                showOfficeForm(e.lngLat);
            }
        });
        
        // Add a way to cancel the selection by clicking the button again
        const cancelHandler = function() {
            map.off('click', handleMapClick);
            // Restore original handlers
            map.off('click');
            originalClickHandlers.forEach(handler => {
                map.on('click', handler.listener);
            });
            
            // Reset the button and cursor
            addButton.disabled = false;
            addButton.textContent = 'Add Office';
            document.body.style.cursor = '';
            map.getCanvas().style.cursor = '';
            addButton.removeEventListener('click', cancelHandler);
            addButton.addEventListener('click', arguments.callee);
        };
        
        // Update the click handler to cancel on second click
        addButton.removeEventListener('click', arguments.callee);
        addButton.addEventListener('click', cancelHandler);
    });

                
</script>

</body>
</html>