// Route Finder using Footwalk Network
// Finds the shortest path from user location to destination using footwalk geojson data

class RouteFinder {
    constructor(map) {
        this.map = map;
        this.footwalkNetwork = null;
        this.routeSourceId = 'user-route';
        this.routeLayerId = 'user-route-layer';
    }

    /**
     * Load footwalk network from geojson
     */
    async loadFootwalkNetwork() {
        try {
            // Determine correct path based on current page location
            const currentPath = window.location.pathname;
            let geojsonPath;
            if (currentPath.includes('/student/') || currentPath.includes('/guest/')) {
                geojsonPath = '../geojson/chmsu.geojson';
            } else if (currentPath.includes('/admin/')) {
                geojsonPath = '../../geojson/chmsu.geojson';
            } else {
                geojsonPath = 'geojson/chmsu.geojson';
            }
            
            console.log('Loading footwalk network from:', geojsonPath);
            const response = await fetch(geojsonPath);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const geojson = await response.json();
            
            // Extract only footwalk features
            const footwalkFeatures = geojson.features.filter(
                feature => feature.properties && feature.properties.footwalk !== undefined
            );
            
            this.footwalkNetwork = {
                type: 'FeatureCollection',
                features: footwalkFeatures
            };
            
            console.log(`✓ Loaded ${footwalkFeatures.length} footwalk segments`);
            return this.footwalkNetwork;
        } catch (error) {
            console.error('Error loading footwalk network:', error);
            return null;
        }
    }

    /**
     * Find nearest point on footwalk network to given coordinates
     */
    findNearestFootwalkPoint(lng, lat) {
        if (!this.footwalkNetwork) return null;

        let nearestPoint = null;
        let minDistance = Infinity;

        this.footwalkNetwork.features.forEach(feature => {
            if (feature.geometry.type === 'LineString') {
                const coordinates = feature.geometry.coordinates;
                
                // Check each segment of the line
                for (let i = 0; i < coordinates.length - 1; i++) {
                    const [lng1, lat1] = coordinates[i];
                    const [lng2, lat2] = coordinates[i + 1];
                    
                    // Find closest point on this segment
                    const point = this.closestPointOnSegment(lng, lat, lng1, lat1, lng2, lat2);
                    const distance = this.haversineDistance(lng, lat, point[0], point[1]);
                    
                    if (distance < minDistance) {
                        minDistance = distance;
                        nearestPoint = {
                            coordinates: point,
                            feature: feature,
                            segmentIndex: i
                        };
                    }
                }
            }
        });

        return nearestPoint;
    }

    /**
     * Find closest point on a line segment to a given point
     */
    closestPointOnSegment(px, py, x1, y1, x2, y2) {
        const dx = x2 - x1;
        const dy = y2 - y1;
        const lengthSquared = dx * dx + dy * dy;
        
        if (lengthSquared === 0) {
            return [x1, y1];
        }
        
        const t = Math.max(0, Math.min(1, ((px - x1) * dx + (py - y1) * dy) / lengthSquared));
        return [x1 + t * dx, y1 + t * dy];
    }

    /**
     * Calculate Haversine distance between two points in meters
     */
    haversineDistance(lng1, lat1, lng2, lat2) {
        const R = 6371000; // Earth radius in meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLng / 2) * Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    /**
     * Build graph from footwalk network
     */
    buildGraph() {
        if (!this.footwalkNetwork) return null;

        const graph = new Map(); // node -> [{node, distance, feature}]
        const nodeMap = new Map(); // coordinate string -> node index
        const tolerance = 0.000001; // Tolerance for matching coordinates (approximately 0.1 meters)

        // Helper function to find or create node key
        const getNodeKey = (lng, lat) => {
            // Check if a similar node already exists (within tolerance)
            for (const [key, node] of nodeMap.entries()) {
                const dist = this.haversineDistance(lng, lat, node.lng, node.lat);
                if (dist < 1) { // Within 1 meter, consider it the same node
                    return key;
                }
            }
            // Create new node key
            return `${lng.toFixed(6)},${lat.toFixed(6)}`;
        };

        // Add all nodes and edges
        this.footwalkNetwork.features.forEach((feature, featureIndex) => {
            if (feature.geometry.type === 'LineString') {
                const coordinates = feature.geometry.coordinates;
                
                for (let i = 0; i < coordinates.length; i++) {
                    const [lng, lat] = coordinates[i];
                    const nodeKey = getNodeKey(lng, lat);
                    
                    if (!nodeMap.has(nodeKey)) {
                        nodeMap.set(nodeKey, {
                            lng,
                            lat,
                            key: nodeKey
                        });
                        graph.set(nodeKey, []);
                    }
                    
                    // Connect to previous node
                    if (i > 0) {
                        const [prevLng, prevLat] = coordinates[i - 1];
                        const prevKey = getNodeKey(prevLng, prevLat);
                        const distance = this.haversineDistance(prevLng, prevLat, lng, lat);
                        
                        if (!graph.has(prevKey)) {
                            graph.set(prevKey, []);
                        }
                        
                        // Check if edge already exists
                        const existingEdge = graph.get(prevKey).find(e => e.node === nodeKey);
                        if (!existingEdge) {
                            graph.get(prevKey).push({
                                node: nodeKey,
                                distance: distance,
                                feature: featureIndex
                            });
                        }
                        
                        if (!graph.has(nodeKey)) {
                            graph.set(nodeKey, []);
                        }
                        
                        const existingReverseEdge = graph.get(nodeKey).find(e => e.node === prevKey);
                        if (!existingReverseEdge) {
                            graph.get(nodeKey).push({
                                node: prevKey,
                                distance: distance,
                                feature: featureIndex
                            });
                        }
                    }
                }
            }
        });

        // Connect nodes that are very close together (intersections)
        const nodeArray = Array.from(nodeMap.entries());
        for (let i = 0; i < nodeArray.length; i++) {
            const [key1, node1] = nodeArray[i];
            for (let j = i + 1; j < nodeArray.length; j++) {
                const [key2, node2] = nodeArray[j];
                const distance = this.haversineDistance(node1.lng, node1.lat, node2.lng, node2.lat);
                // Connect nodes within 5 meters (likely intersections)
                if (distance < 5 && key1 !== key2) {
                    // Check if edge already exists
                    const edges1 = graph.get(key1) || [];
                    const edges2 = graph.get(key2) || [];
                    
                    if (!edges1.find(e => e.node === key2)) {
                        edges1.push({ node: key2, distance: distance, feature: -1 });
                    }
                    if (!edges2.find(e => e.node === key1)) {
                        edges2.push({ node: key1, distance: distance, feature: -1 });
                    }
                }
            }
        }

        const totalEdges = Array.from(graph.values()).reduce((sum, edges) => sum + edges.length, 0) / 2;
        console.log(`Graph built: ${graph.size} nodes, ${totalEdges} edges`);
        return { graph, nodeMap };
    }

    /**
     * Find shortest path using Dijkstra's algorithm
     */
    findShortestPath(startLng, startLat, endLng, endLat) {
        if (!this.footwalkNetwork) {
            console.warn('Footwalk network not loaded');
            return null;
        }

        const { graph, nodeMap } = this.buildGraph();
        if (!graph || graph.size === 0) {
            console.warn('No footwalk network available');
            return null;
        }

        // Find nearest nodes to start and end points
        const startNode = this.findNearestFootwalkPoint(startLng, startLat);
        const endNode = this.findNearestFootwalkPoint(endLng, endLat);

        if (!startNode || !endNode) {
            console.warn('Could not find nearest footwalk points');
            return null;
        }

        const startCoord = startNode.coordinates;
        const endCoord = endNode.coordinates;

        // Find closest existing nodes in the graph
        let closestStartNode = null;
        let closestEndNode = null;
        let minStartDist = Infinity;
        let minEndDist = Infinity;

        nodeMap.forEach((node, key) => {
            const distStart = this.haversineDistance(startCoord[0], startCoord[1], node.lng, node.lat);
            const distEnd = this.haversineDistance(endCoord[0], endCoord[1], node.lng, node.lat);
            
            if (distStart < minStartDist) {
                minStartDist = distStart;
                closestStartNode = key;
            }
            if (distEnd < minEndDist) {
                minEndDist = distEnd;
                closestEndNode = key;
            }
        });

        if (!closestStartNode || !closestEndNode) {
            console.warn('Could not find closest nodes');
            return null;
        }

        // Dijkstra's algorithm
        const distances = new Map();
        const previous = new Map();
        const unvisited = new Set();

        nodeMap.forEach((node, key) => {
            distances.set(key, Infinity);
            unvisited.add(key);
        });

        distances.set(closestStartNode, 0);

        while (unvisited.size > 0) {
            // Find unvisited node with smallest distance
            let currentNode = null;
            let minDist = Infinity;

            unvisited.forEach(nodeKey => {
                const dist = distances.get(nodeKey);
                if (dist < minDist) {
                    minDist = dist;
                    currentNode = nodeKey;
                }
            });

            if (currentNode === null || currentNode === closestEndNode) {
                break;
            }

            unvisited.delete(currentNode);

            // Update distances to neighbors
            const neighbors = graph.get(currentNode) || [];
            neighbors.forEach(neighbor => {
                if (unvisited.has(neighbor.node)) {
                    const alt = distances.get(currentNode) + neighbor.distance;
                    if (alt < distances.get(neighbor.node)) {
                        distances.set(neighbor.node, alt);
                        previous.set(neighbor.node, currentNode);
                    }
                }
            });
        }

        // Reconstruct path
        const path = [];
        let current = closestEndNode;

        if (current === null || distances.get(current) === Infinity) {
            console.warn('No path found to destination');
            return null;
        }

        while (current !== undefined && current !== null) {
            const node = nodeMap.get(current);
            if (node) {
                path.unshift([node.lng, node.lat]);
            }
            current = previous.get(current);
        }

        // Add start and end points if path was found
        if (path.length > 0) {
            path.unshift(startCoord);
            path.push(endCoord);
            return path;
        }

        return null;
    }

    /**
     * Display route on map
     */
    displayRoute(routeCoordinates) {
        if (!routeCoordinates || routeCoordinates.length < 2) {
            console.warn('Invalid route coordinates:', routeCoordinates);
            return;
        }

        console.log('Displaying route with', routeCoordinates.length, 'points');

        // Ensure map is loaded
        if (!this.map.loaded()) {
            console.log('Map not loaded, waiting...');
            this.map.once('load', () => {
                this.displayRoute(routeCoordinates);
            });
            return;
        }

        // Remove existing route if any
        this.removeRoute();

        // Create GeoJSON for route
        const routeGeoJSON = {
            type: 'Feature',
            geometry: {
                type: 'LineString',
                coordinates: routeCoordinates
            },
            properties: {}
        };

        try {
            // Add source
            if (!this.map.getSource(this.routeSourceId)) {
                this.map.addSource(this.routeSourceId, {
                    type: 'geojson',
                    data: routeGeoJSON
                });
                console.log('Route source added');
            } else {
                this.map.getSource(this.routeSourceId).setData(routeGeoJSON);
                console.log('Route source updated');
            }

            // Add layer if it doesn't exist
            if (!this.map.getLayer(this.routeLayerId)) {
                this.map.addLayer({
                    id: this.routeLayerId,
                    type: 'line',
                    source: this.routeSourceId,
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round'
                    },
                    paint: {
                        'line-color': '#00bcd4', // Cyan blue
                        'line-width': 4,
                        'line-opacity': 0.8
                    }
                });
                console.log('Route layer added');
            }

            console.log('✓ Route displayed on map');
        } catch (error) {
            console.error('Error displaying route:', error);
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

    /**
     * Update route from current location to destination
     */
    async updateRoute(currentLng, currentLat, destinationLng, destinationLat) {
        try {
            console.log('Updating route from:', [currentLng, currentLat], 'to:', [destinationLng, destinationLat]);
            
            if (!this.footwalkNetwork) {
                console.log('Footwalk network not loaded, loading now...');
                const loaded = await this.loadFootwalkNetwork();
                if (!loaded) {
                    console.error('Failed to load footwalk network');
                    return null;
                }
            }

            if (!this.footwalkNetwork || this.footwalkNetwork.features.length === 0) {
                console.warn('No footwalk features available');
                return null;
            }

            // Ensure map is loaded
            if (!this.map.loaded()) {
                console.log('Map not loaded yet, waiting...');
                await new Promise((resolve) => {
                    this.map.once('load', resolve);
                });
            }

            const route = this.findShortestPath(currentLng, currentLat, destinationLng, destinationLat);
            
            if (route && route.length > 1) {
                console.log('Route found with', route.length, 'points');
                this.displayRoute(route);
                return route;
            } else {
                console.warn('Could not find route - no path found');
                return null;
            }
        } catch (error) {
            console.error('Error updating route:', error);
            return null;
        }
    }
}

