// Admin Map Functionality
let newOfficeMarker = null;
let currentPopup = null; // Track the currently open popup

// Initialize the map click handler
function initMapClickHandler(map) {
    // This function is intentionally left empty as we don't want any automatic click behavior
    // on the map. The office addition is now handled by the 'Add Office' button click.
}

// Show the office form modal
async function showOfficeForm(lngLat) {
    // Close any existing modal
    closeModal();
    
    // Load categories
    let categories = [];
    try {
        const response = await fetch('../../api/get_categories.php');
        console.log('Categories API response status:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            console.log('Categories data:', data);
            categories = data.categories || [];
        } else {
            const errorText = await response.text();
            console.error('Categories API error:', response.status, errorText);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
    
    console.log('Loaded categories:', categories);
    
    // If no categories loaded, show a message
    let categoryOptions = '';
    if (categories.length === 0) {
        categoryOptions = '<option value="">No categories available</option>';
    } else {
        categoryOptions = categories.map(cat => 
            `<option value="${cat.category_id}">${cat.name}</option>`
        ).join('');
    }
    
    const modal = document.createElement('div');
    modal.id = 'office-form-modal';
    modal.className = 'modal';
    modal.style.display = 'block';
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Office</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="office-form" enctype="multipart/form-data">
                    <input type="hidden" id="location-lat" value="${lngLat.lat}">
                    <input type="hidden" id="location-lng" value="${lngLat.lng}">
                    
                    <div class="form-group">
                        <label for="office-name">Office Name:</label>
                        <input type="text" id="office-name" name="office_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="office-category">Category:</label>
                        <select id="office-category" name="category_id" required>
                            <option value="">Select a category</option>
                            ${categoryOptions}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="office-description">Description:</label>
                        <textarea id="office-description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Location:</label>
                        <div class="coordinates">
                            <span>Latitude: ${lngLat.lat.toFixed(6)}</span>
                            <span>Longitude: ${lngLat.lng.toFixed(6)}</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="marker-image">Marker Image:</label>
                        <input type="file" id="marker-image" name="marker_image" accept="image/*" required>
                        <small>This image will be used as the map marker</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="content-images">Content Images (up to 4):</label>
                        <input type="file" id="content-images" name="content_images[]" accept="image/*" multiple>
                        <div id="image-preview" class="image-preview"></div>
                    </div>
                    
                    <div id="form-message" style="display: none; padding: 10px; margin: 10px 0; border-radius: 4px;"></div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancel-office" class="btn-cancel">Cancel</button>
                        <button type="submit" class="btn-submit" id="submit-btn">Add Office</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add click handler for the modal background to close
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Add keyboard event for Escape key
    const handleEscape = function(e) {
        if (e.key === 'Escape') {
            closeModal();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
    
    // Handle form submission - wait for DOM to be ready
    setTimeout(() => {
        const form = document.getElementById('office-form');
        if (!form) {
            console.error('Form not found!');
            return;
        }
        
        console.log('Form found, attaching submit handler');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Form submitted');
            
            const submitBtn = document.getElementById('submit-btn');
            const messageDiv = document.getElementById('form-message');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
            messageDiv.style.display = 'none';
            
            const formData = new FormData(form);
            formData.append('location_lat', lngLat.lat);
            formData.append('location_lng', lngLat.lng);
            
            console.log('Sending data to API:', {
                office_name: formData.get('office_name'),
                category_id: formData.get('category_id'),
                location_lat: lngLat.lat,
                location_lng: lngLat.lng
            });
            
            try {
                const response = await fetch('../../api/create_office.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Response data:', result);
                
                if (result.status === 'success') {
                    messageDiv.style.display = 'block';
                    messageDiv.style.backgroundColor = '#4CAF50';
                    messageDiv.style.color = 'white';
                    messageDiv.textContent = result.message || 'Office added successfully!';
                    
                    // Reload page after 1.5 seconds to show new marker
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    messageDiv.style.display = 'block';
                    messageDiv.style.backgroundColor = '#f44336';
                    messageDiv.style.color = 'white';
                    messageDiv.textContent = result.message || 'Failed to add office.';
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                messageDiv.style.display = 'block';
                messageDiv.style.backgroundColor = '#f44336';
                messageDiv.style.color = 'white';
                messageDiv.textContent = 'Error: ' + error.message;
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }, 100);
    
    // Handle cancel button
    document.getElementById('cancel-office')?.addEventListener('click', closeModal);
    document.querySelector('.close-modal')?.addEventListener('click', closeModal);
    
    // Handle image preview
    document.getElementById('content-images')?.addEventListener('change', handleImagePreview);
}

// Function to show edit office form
async function showEditOfficeForm(building) {
    // Close any existing modal
    closeModal();
    
    const officeId = building.office_id;
    if (!officeId) {
        alert('Office ID not found. Cannot edit this office.');
        return;
    }
    
    // Fetch office data from API
    let officeData = null;
    try {
        const response = await fetch(`../../api/get_office.php?office_id=${officeId}`);
        const result = await response.json();
        if (result.status === 'success') {
            officeData = result.office;
        } else {
            alert('Failed to load office data: ' + result.message);
            return;
        }
    } catch (error) {
        console.error('Error fetching office data:', error);
        alert('Failed to load office data. Please try again.');
        return;
    }
    
    // Fetch categories
    let categories = [];
    try {
        const catResponse = await fetch('../../api/get_categories.php');
        const catResult = await catResponse.json();
        if (catResult.status === 'success') {
            categories = catResult.categories || [];
        }
    } catch (error) {
        console.error('Error fetching categories:', error);
    }
    
    const categoryOptions = categories.map(cat => 
        `<option value="${cat.category_id}" ${cat.category_id == officeData.category_id ? 'selected' : ''}>${cat.name}</option>`
    ).join('');
    
    // Build existing images HTML
    const existingImages = [];
    if (officeData.marker_image) {
        existingImages.push({
            path: officeData.marker_image,
            isPrimary: true,
            label: 'Marker Image'
        });
    }
    if (officeData.content_images && officeData.content_images.length > 0) {
        officeData.content_images.forEach(img => {
            existingImages.push({
                path: img,
                isPrimary: false,
                label: 'Content Image'
            });
        });
    }
    
    const existingImagesHTML = existingImages.map((img, index) => {
        const imageUrl = img.path.startsWith('../') ? img.path : `../../${img.path}`;
        return `
            <div class="existing-image-item" data-image-path="${img.path}">
                <div class="image-preview">
                    <img src="${imageUrl}" alt="${img.label}" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 4px;">
                    <span class="image-label">${img.label}</span>
                </div>
                <button type="button" class="delete-image-btn" data-image-path="${img.path}">Delete</button>
            </div>
        `;
    }).join('');
    
    const modal = document.createElement('div');
    modal.id = 'office-form-modal';
    modal.className = 'modal';
    modal.style.display = 'block';
    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2>Edit Office</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="edit-office-form" enctype="multipart/form-data">
                    <input type="hidden" id="edit-office-id" value="${officeId}">
                    
                    <div class="form-group">
                        <label for="edit-office-name">Office Name:</label>
                        <input type="text" id="edit-office-name" name="office_name" value="${officeData.office_name || ''}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-office-description">Description:</label>
                        <textarea id="edit-office-description" name="description" rows="3" required>${officeData.description || ''}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-category-id">Category:</label>
                        <select id="edit-category-id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            ${categoryOptions}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Location:</label>
                        <div class="coordinates">
                            <span>Latitude: ${officeData.location_lat}</span>
                            <span>Longitude: ${officeData.location_lng}</span>
                        </div>
                        <small class="form-text text-muted">Location cannot be changed. Delete and recreate the office to change location.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Existing Images:</label>
                        <div id="existing-images-container" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                            ${existingImagesHTML || '<p style="color: #666;">No images uploaded yet.</p>'}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-marker-image">Marker Image (optional - upload new to replace):</label>
                        <input type="file" id="edit-marker-image" name="marker_image" accept="image/*">
                        <small class="form-text text-muted">Upload a new marker image to replace the existing one.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-content-images">Content Images (up to 4, optional):</label>
                        <input type="file" id="edit-content-images" name="content_images[]" accept="image/*" multiple>
                        <small class="form-text text-muted">You can upload up to 4 content images. Existing images can be deleted above.</small>
                    </div>
                    
                    <input type="hidden" id="deleted-images-input" name="deleted_images" value="[]">
                    
                    <div id="edit-form-message" style="display: none; padding: 10px; margin: 10px 0; border-radius: 4px;"></div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancel-edit-office" class="btn-cancel">Cancel</button>
                        <button type="submit" class="btn-submit" id="edit-submit-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Track deleted images
    const deletedImages = [];
    
    // Handle delete image buttons
    modal.querySelectorAll('.delete-image-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const imagePath = this.dataset.imagePath;
            if (confirm('Are you sure you want to delete this image?')) {
                deletedImages.push(imagePath);
                this.closest('.existing-image-item').remove();
                document.getElementById('deleted-images-input').value = JSON.stringify(deletedImages);
            }
        });
    });
    
    // Handle form submission
    const form = document.getElementById('edit-office-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        const submitBtn = document.getElementById('edit-submit-btn');
        const messageDiv = document.getElementById('edit-form-message');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Updating...';
        messageDiv.style.display = 'none';
        
        const formData = new FormData(form);
        formData.append('office_id', officeId);
        formData.append('deleted_images', JSON.stringify(deletedImages));
        
        try {
            const response = await fetch('../../api/update_office.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.status === 'success') {
                messageDiv.style.display = 'block';
                messageDiv.style.backgroundColor = '#4caf50';
                messageDiv.style.color = 'white';
                messageDiv.textContent = result.message || 'Office updated successfully!';
                
                // Reload page after a short delay to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                messageDiv.style.display = 'block';
                messageDiv.style.backgroundColor = '#f44336';
                messageDiv.style.color = 'white';
                messageDiv.textContent = result.message || 'Failed to update office.';
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        } catch (error) {
            console.error('Error updating office:', error);
            messageDiv.style.display = 'block';
            messageDiv.style.backgroundColor = '#f44336';
            messageDiv.style.color = 'white';
            messageDiv.textContent = 'An error occurred while updating the office. Please try again.';
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
    
    // Handle cancel button
    document.getElementById('cancel-edit-office')?.addEventListener('click', closeModal);
    modal.querySelector('.close-modal')?.addEventListener('click', closeModal);
    
    // Close on background click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
}

// Make functions available globally
if (typeof window !== 'undefined') {
    window.showOfficeForm = showOfficeForm;
    window.showEditOfficeForm = showEditOfficeForm;
    window.closeModal = closeModal;
}

// Handle image preview
function handleImagePreview(e) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = ''; // Clear previous previews
    
    const files = e.target.files;
    if (files.length > 4) {
        alert('You can only upload up to 4 images');
        e.target.value = '';
        return;
    }
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/')) {
            continue;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'preview-image';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    }
}

// Close the modal and clean up
function closeModal() {
    const modal = document.getElementById('office-form-modal');
    if (modal) {
        modal.remove();
    }
    if (newOfficeMarker) {
        newOfficeMarker.remove();
        newOfficeMarker = null;
    }
}
