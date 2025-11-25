/**
 * Modern Setup Modal JavaScript
 * Handles modal interactions and animations
 */

class SetupModal {
    constructor() {
        this.modal = null;
        this.isAnimating = false;
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
        } else {
            this.setupEventListeners();
        }
    }

    setupEventListeners() {
        // Find modal elements
        this.modal = document.querySelector('.seup-modal');
        
        if (!this.modal) return;

        // Close button functionality
        const closeButtons = this.modal.querySelectorAll('.seup-modal-close, .seup-modal-close-btn');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.closeModal();
            });
        });

        // Close on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('show')) {
                this.closeModal();
            }
        });

        // Animate check items on modal show
        if (this.modal.classList.contains('show')) {
            this.animateCheckItems();
        }

        // Setup check items styling
        this.setupCheckItems();
    }

    setupCheckItems() {
        const checkItems = document.querySelectorAll('.setup-check-item');
        
        checkItems.forEach((item, index) => {
            const icon = item.querySelector('i');
            
            if (icon) {
                // Determine if item is completed based on icon class
                const isCompleted = icon.classList.contains('fa-check-circle');
                
                if (isCompleted) {
                    item.classList.add('completed');
                    item.classList.remove('incomplete');
                } else {
                    item.classList.add('incomplete');
                    item.classList.remove('completed');
                }

                // Wrap icon in styled container
                if (!item.querySelector('.setup-check-icon')) {
                    const iconContainer = document.createElement('div');
                    iconContainer.className = 'setup-check-icon';
                    iconContainer.appendChild(icon.cloneNode(true));
                    
                    // Replace original icon with container
                    icon.parentNode.replaceChild(iconContainer, icon);
                }

                // Wrap text content
                const textElements = Array.from(item.childNodes).filter(node => 
                    node.nodeType === Node.TEXT_NODE || 
                    (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'SPAN')
                );

                if (textElements.length > 0 && !item.querySelector('.setup-check-content')) {
                    const contentContainer = document.createElement('div');
                    contentContainer.className = 'setup-check-content';
                    
                    const title = document.createElement('h4');
                    title.className = 'setup-check-title';
                    
                    const description = document.createElement('p');
                    description.className = 'setup-check-description';

                    // Extract text content
                    const spanElement = item.querySelector('span');
                    if (spanElement) {
                        title.textContent = spanElement.textContent;
                        description.textContent = isCompleted ? 
                            'Konfiguracija je završena' : 
                            'Potrebna je konfiguracija';
                        
                        contentContainer.appendChild(title);
                        contentContainer.appendChild(description);
                        
                        // Insert after icon container
                        const iconContainer = item.querySelector('.setup-check-icon');
                        if (iconContainer) {
                            iconContainer.insertAdjacentElement('afterend', contentContainer);
                        }
                        
                        // Remove original span
                        spanElement.remove();
                    }
                }
            }

            // Add staggered animation delay
            item.style.animationDelay = `${index * 0.1}s`;
        });
    }

    animateCheckItems() {
        const checkItems = document.querySelectorAll('.setup-check-item');
        
        checkItems.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                item.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                
                requestAnimationFrame(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                });
            }, index * 100);
        });
    }

    openModal() {
        if (this.isAnimating || !this.modal) return;
        
        this.isAnimating = true;
        document.body.style.overflow = 'hidden';
        
        this.modal.classList.add('show');
        
        setTimeout(() => {
            this.isAnimating = false;
            this.animateCheckItems();
        }, 400);
    }

    closeModal() {
        if (this.isAnimating || !this.modal) return;
        
        this.isAnimating = true;
        
        this.modal.classList.remove('show');
        
        setTimeout(() => {
            this.isAnimating = false;
            document.body.style.overflow = '';
        }, 400);
    }

    // Public method to show modal programmatically
    show() {
        this.openModal();
    }

    // Public method to hide modal programmatically
    hide() {
        this.closeModal();
    }
}

// Initialize modal when script loads
const setupModal = new SetupModal();

// Make it globally available
window.SetupModal = setupModal;

// Additional utility functions
window.setupModalUtils = {
    // Function to update check item status
    updateCheckItem: function(itemIndex, isCompleted) {
        const items = document.querySelectorAll('.setup-check-item');
        if (items[itemIndex]) {
            const item = items[itemIndex];
            const icon = item.querySelector('.setup-check-icon i');
            
            if (isCompleted) {
                item.classList.add('completed');
                item.classList.remove('incomplete');
                if (icon) {
                    icon.className = 'fas fa-check-circle';
                }
            } else {
                item.classList.add('incomplete');
                item.classList.remove('completed');
                if (icon) {
                    icon.className = 'fas fa-exclamation-triangle';
                }
            }
        }
    },

    // Function to add custom animations
    addCustomAnimation: function(element, animationClass) {
        element.classList.add(animationClass);
        element.addEventListener('animationend', () => {
            element.classList.remove(animationClass);
        }, { once: true });
    }
};