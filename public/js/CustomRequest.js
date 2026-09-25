/**
 * Custom Request Handler
 */
var CustomRequest = {
    /**
     * Show the create custom request modal
     */
    showCreateModal: function() {
        const modalElement = document.getElementById('createCustomRequestModal');
        if (!modalElement) return;
        
        // Try Bootstrap 5 first
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                // Try to get existing instance first
                let modal = bootstrap.Modal.getInstance(modalElement);
                if (!modal) {
                    // If no instance exists, create a new one
                    modal = new bootstrap.Modal(modalElement);
                }
                modal.show();
                return;
            } catch (e) {
                // If Bootstrap 5 fails, fall through to jQuery
                console.log('Bootstrap 5 modal failed, trying jQuery');
            }
        }
        
        // Try jQuery/Bootstrap 4
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#createCustomRequestModal').modal('show');
            return;
        }
        
        // Fallback - manual show
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        document.body.classList.add('modal-open');
        
        // Add backdrop
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    },

    /**
     * Hide the create custom request modal
     */
    hideCreateModal: function() {
        const modalElement = document.getElementById('createCustomRequestModal');
        if (!modalElement) return;
        
        // Try Bootstrap 5 first
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                // Try to get existing instance
                let modal = bootstrap.Modal.getInstance(modalElement);
                if (!modal) {
                    // If no instance exists, create a new one
                    modal = new bootstrap.Modal(modalElement);
                }
                modal.hide();
                return;
            } catch (e) {
                // If Bootstrap 5 fails, fall through to jQuery
                console.log('Bootstrap 5 modal failed, trying jQuery');
            }
        }
        
        // Try jQuery/Bootstrap 4
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#createCustomRequestModal').modal('hide');
            return;
        }
        
        // Fallback - manual hide
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.parentNode.removeChild(backdrop);
        }
    },

    /**
     * Initialize custom request functionality
     */
    init: function() {
        const form = document.getElementById('createCustomRequestForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                CustomRequest.submitForm(form);
            });
        }

        // Handle modal close buttons
        const modal = document.getElementById('createCustomRequestModal');
        if (modal) {
            const closeButtons = modal.querySelectorAll('[data-dismiss="modal"], .close');
            closeButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    CustomRequest.hideCreateModal();
                });
            });
        }
    },

    /**
     * Submit the create request form
     */
    submitForm: function(form) {
        const formData = new FormData(form);
        const data = {};
        
        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            if (key === '_token') {
                continue; // Skip CSRF token, it's handled in headers
            }
            if (key === 'price' || key === 'goal_amount') {
                data[key] = value ? parseFloat(value) : null;
            } else if (key === 'deadline') {
                data[key] = value || null;
            } else if (key === 'message_id') {
                data[key] = value ? parseInt(value) : null;
            } else if (key === 'creator_id') {
                data[key] = value ? parseInt(value) : null;
            } else if (key === 'creator_username') {
                // Include creator_username if creator_id is not set
                if (!data['creator_id'] && value) {
                    data['creator_username'] = value;
                }
            } else {
                data[key] = value;
            }
        }

        // Validate required fields
        if (!data.creator_id && !data.creator_username) {
            if (typeof launchToast !== 'undefined') {
                launchToast('danger', 'Error', 'Please enter and select a creator from the search results');
            } else {
                alert('Please enter and select a creator from the search results');
            }
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';

        fetch('/custom-requests', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(async response => {
            // Get content type to check if it's JSON
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');
            
            // Try to parse as JSON if it's JSON, otherwise get text
            let responseData;
            if (isJson) {
                try {
                    responseData = await response.json();
                } catch (e) {
                    // If JSON parsing fails, treat as error
                    throw { message: 'Invalid response from server' };
                }
            } else {
                // If not JSON, get text (might be HTML error page)
                const text = await response.text();
                throw { message: 'Server error occurred. Please try again.' };
            }
            
            // Check if response is ok (status 200-299)
            if (response.ok) {
                return responseData;
            } else {
                // For error responses, throw the error data
                throw responseData;
            }
        })
        .then(data => {
            if (data.success) {
                // Check if payment is required
                if (data.requires_payment && data.upfront_payment) {
                    // Show payment modal or redirect to payment
                    const proceedPayment = confirm(
                        'Upfront payment of $' + parseFloat(data.upfront_payment).toFixed(2) + 
                        ' is required to create this request. Proceed to payment?'
                    );
                    
                    if (proceedPayment) {
                        // Redirect to payment page
                        if (data.payment_url) {
                            window.location.href = data.payment_url;
                        } else {
                            // Fallback: redirect to request page where they can pay
                            window.location.href = '/custom-requests/' + data.request.id;
                        }
                        return;
                    } else {
                        // User cancelled, just show message
                        if (typeof launchToast !== 'undefined') {
                            launchToast('info', 'Info', 'Request created but payment is required to proceed.');
                        }
                    }
                } else {
                    // No payment required or already paid
                    // Reset form
                    form.reset();
                    
                    // Clear creator selection indicator
                    const creatorIndicator = document.getElementById('creator_selected_indicator');
                    const creatorResults = document.getElementById('creator_search_results');
                    if (creatorIndicator) creatorIndicator.style.display = 'none';
                    if (creatorResults) creatorResults.style.display = 'none';
                    
                    // Hide modal immediately
                    CustomRequest.hideCreateModal();
                    
                    // Show success toast
                    if (typeof launchToast !== 'undefined') {
                        launchToast('success', 'Success', data.message || 'Custom request created successfully!');
                    }
                    
                    // Redirect after a short delay
                    setTimeout(function() {
                        window.location.href = data.redirect || (data.request && data.request.id ? '/custom-requests/' + data.request.id : '/custom-requests/marketplace');
                    }, 500);
                }
            } else {
                // Show error
                const errorMsg = data.message || 'Failed to create request';
                if (typeof launchToast !== 'undefined') {
                    launchToast('danger', 'Error', errorMsg);
                } else {
                    alert(errorMsg);
                }
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Show error message from response if available
            const errorMessage = (error && error.message) ? error.message : 'An error occurred. Please try again.';
            if (typeof launchToast !== 'undefined') {
                launchToast('danger', 'Error', errorMessage);
            } else {
                alert(errorMessage);
            }
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        CustomRequest.init();
    });
} else {
    CustomRequest.init();
}
