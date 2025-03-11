/**
 * EduCoach main JavaScript file
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('EduCoach scripts initialized');
    
    // Initialize tooltips if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Handle profile image upload form
    const profileImageForm = document.querySelector('form[enctype="multipart/form-data"]');
    const profileImageInput = document.getElementById('profile_image');
    
    if (profileImageForm && profileImageInput) {
        // Show filename when image is selected
        profileImageInput.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'No file chosen';
            const fileNameDisplay = document.querySelector('.custom-file-label');
            if (fileNameDisplay) {
                fileNameDisplay.textContent = fileName;
            }
            
            // Show preview if there's a selected file
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewImg = document.querySelector('.img-preview');
                    if (previewImg) {
                        previewImg.src = e.target.result;
                        previewImg.style.display = 'block';
                    }
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Handle service tier pricing
    const serviceTierForms = document.querySelectorAll('.service-tier-form');
    
    if (serviceTierForms.length > 0) {
        serviceTierForms.forEach(form => {
            const priceInput = form.querySelector('input[name="price"]');
            const durationInput = form.querySelector('select[name="duration"]');
            
            if (priceInput && durationInput) {
                const updateTotalCost = () => {
                    const price = parseFloat(priceInput.value) || 0;
                    const duration = durationInput.value;
                    const totalCostEl = form.querySelector('.total-cost');
                    
                    if (totalCostEl) {
                        totalCostEl.textContent = `$${price.toFixed(2)} per ${duration}`;
                    }
                };
                
                priceInput.addEventListener('input', updateTotalCost);
                durationInput.addEventListener('change', updateTotalCost);
            }
        });
    }
}); 