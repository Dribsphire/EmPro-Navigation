<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>EmPro</title>
<meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no">
<link rel="icon" type="image/png" href="../images/CHMSU.png">
<link href="https://api.mapbox.com/mapbox-gl-js/v3.17.0-beta.1/mapbox-gl.css" rel="stylesheet">
<link rel="stylesheet" href="../css/guest_style.css">
<script type="text/javascript" src="../script/app.js" defer></script>
<script src="https://api.mapbox.com/mapbox-gl-js/v3.17.0-beta.1/mapbox-gl.js"></script>
<style>
body { margin: 0; padding: 0; }
#map { position: absolute; top: 5px; bottom: 25px; width: 98%; border-radius:20px; }
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
        color: #000;
        padding: 10px;
        border-radius: 5px;
        font-weight: bold;
        font-family: 'Poppins', sans-serif;
        
        }
@media (max-width: 768px) {
        #map { width: 100%; bottom: 65px; border-radius:0px;
            
    }

}
</style>
</head>
<body>
<?php include 'student_nav.php'; ?>   
<div class="container"> 
    <div class="map-search">
        <label class="sr-only" for="building-search">Search buildings</label>
        <input id="building-search" list="building-list" placeholder="Search buildings..." autocomplete="off">
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
    <div id="map"></div>
</div>
<script src="../script/showlayout.js"></script>
<script src="../script/navigation_tracker.js"></script>
<script src="../script/realtime_office_sync.js"></script>
<script>
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
        ...getViewPreset('2d')
    });

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

    // Global array to store all building markers
    let allBuildingMarkers = [];
    window.allBuildingMarkers = allBuildingMarkers; // Make it globally accessible

    // Function to create markers from building/office data
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
            
            // Determine image path - handle both database paths and local paths
            let imagePath = image;
            if (image.startsWith('buildings/')) {
                imagePath = `../../${image}`;
            } else if (image.startsWith('building_content/')) {
                imagePath = `../../${image}`;
            } else if (!image.startsWith('../')) {
                imagePath = `../../buildings/${image}`;
            }
            
            const el = document.createElement('div');
            const width = iconSize[0] || 40;
            const height = iconSize[1] || 40;
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
                <div style="padding: 8px 0;">
                    <p style="margin: 0 0 10px 0; font-weight: bold;">${popup}</p>
                    <div style="display: flex; gap: 8px; flex-direction: column;">
                        <button class="start-navigation-btn" data-building="${name}" data-office-id="${building.office_id || ''}" style="
                            width: 100%;
                            padding: 8px 16px;
                            background-color: #4f46e5;
                            color: white;
                            border: none;
                            border-radius: 6px;
                            font-weight: 600;
                            cursor: pointer;
                            font-size: 14px;
                            transition: background-color 0.2s ease;
                        " onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                            Start Navigation
                        </button>
                        ${formattedGallery.length > 0 ? `
                        <button class="view-content-btn" data-building="${name}" data-gallery='${JSON.stringify(formattedGallery)}' style="
                            width: 100%;
                            padding: 8px 16px;
                            background-color: #10b981;
                            color: white;
                            border: none;
                            border-radius: 6px;
                            font-weight: 600;
                            cursor: pointer;
                            font-size: 14px;
                            transition: background-color 0.2s ease;
                        " onmouseover="this.style.backgroundColor='#059669'" onmouseout="this.style.backgroundColor='#10b981'">
                            View Content
                        </button>` : ''}
                    </div>
                </div>
            `;
            const markerPopup = new mapboxgl.Popup({ offset: 25 }).setHTML(popupHTML);
            
            markerPopup.on('open', () => {
                const contentBtn = markerPopup._content.querySelector('.view-content-btn');
                const navBtn = markerPopup._content.querySelector('.start-navigation-btn');
                
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
                
                if (navBtn) {
                    navBtn.addEventListener('click', () => {
                        const buildingToNavigate = allBuildingMarkers.find(b => 
                            b.name === navBtn.dataset.building || 
                            (b.office_id && b.office_id == navBtn.dataset.officeId)
                        );
                        if (buildingToNavigate && window.navigationTracker) {
                            const officeData = {
                                ...buildingToNavigate,
                                office_id: buildingToNavigate.office_id || null,
                                radius: buildingToNavigate.radius || 50
                            };
                            window.navigationTracker.startNavigation(officeData);
                        } else {
                            console.log('Start navigation to:', navBtn.dataset.building);
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
            window.allBuildingMarkers = allBuildingMarkers; // Update global reference
        });
        
        console.log(`Successfully created ${buildings.length} markers. Total markers: ${allBuildingMarkers.length}`);
    }

    // Load offices from database
    async function loadOfficesFromDatabase() {
        try {
            console.log('Fetching offices from API...');
            const response = await fetch('../../api/get_offices_public.php');
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
                        
                        // Initialize real-time sync with loaded offices
                        if (window.officeSync) {
                            window.officeSync.initialize(data.offices);
                        }
                        
                        return data.offices;
                    } else {
                        console.log('No offices found in database or empty array', data);
                        return [];
                    }
                } catch (parseError) {
                    console.error('Failed to parse JSON response:', parseError);
                    console.error('Response was:', responseText);
                    return [];
                }
            } else {
                console.error('Failed to load offices:', response.status, responseText);
                return [];
            }
        } catch (error) {
            console.error('Error loading offices:', error);
            return [];
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

    // Global navigation tracker
    let navigationTracker = null;
    let officeSync = null;
    
    // Load offices from database and initialize navigation tracker
    async function loadOfficesAndInitTracker() {
        try {
            const response = await fetch('../../api/get_offices_public.php');
            if (response.ok) {
                const data = await response.json();
                if (data.status === 'success' && data.offices && data.offices.length > 0) {
                    // Initialize navigation tracker with database offices
                    navigationTracker = new NavigationTracker(map, data.offices);
                    window.navigationTracker = navigationTracker;
                    
                    // Initialize real-time sync
                    officeSync = new RealtimeOfficeSync(
                        map,
                        createMarkers, // Callback to create new markers
                        (offices) => {
                            // Callback to update navigation tracker
                            if (window.navigationTracker) {
                                // Update navigation tracker with new offices
                                offices.forEach(office => {
                                    const radius = office.radius || 50;
                                    if (!window.navigationTracker.radiusCircles.has(office.office_id || office.name)) {
                                        window.navigationTracker.addRadiusCircle(office, radius);
                                    }
                                });
                            }
                        }
                    );
                    officeSync.initialize(data.offices);
                    window.officeSync = officeSync;
                    
                    // Start real-time polling
                    officeSync.start();
                } else {
                    console.log('No offices found in database');
                }
            } else {
                console.error('Failed to load offices for navigation tracker');
            }
        } catch (error) {
            console.error('Error loading offices for navigation tracker:', error);
        }
    }

    // Check if map is already loaded (sometimes it loads very quickly)
    if (map.loaded()) {
        console.log('Map already loaded, loading offices immediately');
        setTimeout(async () => {
            await loadOfficesFromDatabase();
            await loadOfficesAndInitTracker();
        }, 500);
    }

    map.on('load', async ()=>{
        //set the default atmosphere style
        map.setFog({});
        
        // Load offices from database
        await loadOfficesFromDatabase();
        
        // Initialize navigation tracker and real-time sync
        await loadOfficesAndInitTracker();
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
</script>

</body>
</html>