// Mobile Navigation
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Close mobile menu when clicking on a link
    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });
}

// Form Validation
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        const errorElement = document.getElementById(`${input.name}-error`);
        
        if (!input.value.trim()) {
            showError(errorElement, 'This field is required');
            isValid = false;
        } else if (input.type === 'email' && !isValidEmail(input.value)) {
            showError(errorElement, 'Please enter a valid email address');
            isValid = false;
        } else if (input.type === 'password' && input.value.length < 6) {
            showError(errorElement, 'Password must be at least 6 characters');
            isValid = false;
        } else if (input.type === 'number' && input.hasAttribute('min') && parseFloat(input.value) < parseFloat(input.getAttribute('min'))) {
            showError(errorElement, `Value must be at least ${input.getAttribute('min')}`);
            isValid = false;
        } else if (input.type === 'number' && input.hasAttribute('max') && parseFloat(input.value) > parseFloat(input.getAttribute('max'))) {
            showError(errorElement, `Value must be at most ${input.getAttribute('max')}`);
            isValid = false;
        } else {
            hideError(errorElement);
        }
    });
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showError(element, message) {
    if (element) {
        element.textContent = message;
        element.style.display = 'block';
    }
}

function hideError(element) {
    if (element) {
        element.style.display = 'none';
    }
}

// Real-time form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                const errorElement = document.getElementById(`${this.name}-error`);
                if (!this.value.trim() && this.hasAttribute('required')) {
                    showError(errorElement, 'This field is required');
                } else {
                    hideError(errorElement);
                }
            });
            
            input.addEventListener('input', function() {
                const errorElement = document.getElementById(`${this.name}-error`);
                hideError(errorElement);
            });
        });
    });
});

// Modal functionality
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
});

// BMI Calculator
function calculateBMI() {
    const height = parseFloat(document.getElementById('height')?.value);
    const weight = parseFloat(document.getElementById('weight')?.value);
    
    if (!height || !weight) {
        alert('Please enter both height and weight');
        return;
    }
    
    const heightInMeters = height / 100;
    const bmi = weight / (heightInMeters * heightInMeters);
    
    let category = '';
    let color = '';
    
    if (bmi < 18.5) {
        category = 'Underweight';
        color = '#3498db';
    } else if (bmi < 25) {
        category = 'Normal weight';
        color = '#27ae60';
    } else if (bmi < 30) {
        category = 'Overweight';
        color = '#f39c12';
    } else {
        category = 'Obese';
        color = '#e74c3c';
    }
    
    const resultElement = document.getElementById('bmi-result');
    if (resultElement) {
        resultElement.innerHTML = `
            <div class="stat-card">
                <div class="stat-icon bmi">📊</div>
                <div class="stat-content">
                    <h3>Your BMI Result</h3>
                    <div class="stat-value">${bmi.toFixed(1)}</div>
                    <div class="rank-badge" style="background: ${color}">${category}</div>
                    <p>Height: ${height}cm | Weight: ${weight}kg</p>
                </div>
            </div>
        `;
    }
    
    return { bmi: bmi.toFixed(1), category, color };
}

function getBMIColor(bmi) {
    if (bmi < 18.5) return '#3498db';
    if (bmi < 25) return '#27ae60';
    if (bmi < 30) return '#f39c12';
    return '#e74c3c';
}

// Smooth scrolling for anchor links
document.addEventListener('DOMContentLoaded', function() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const headerHeight = document.querySelector('.header').offsetHeight;
                const targetPosition = targetElement.offsetTop - headerHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
});

// Initialize charts if Chart.js is available
function initializeCharts() {
    if (typeof Chart !== 'undefined') {
        const progressCtx = document.getElementById('progress-chart');
        if (progressCtx) {
            new Chart(progressCtx, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                    datasets: [{
                        label: 'Workout Points',
                        data: [12, 19, 15, 25, 22, 30],
                        borderColor: '#4a90e2',
                        backgroundColor: 'rgba(74, 144, 226, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    }
}

// Load user stats via AJAX
function loadUserStats() {
    fetch('get_user_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update stats on the page
                updateStatsDisplay(data.data);
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function updateStatsDisplay(stats) {
    // This function would update various stat elements on the page
    // Implementation depends on specific page structure
}

// Form submission handlers
function handleWorkoutSubmission(form) {
    if (!validateForm(form)) return false;
    
    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Logging...';
    submitButton.disabled = true;
    
    return true;
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Set current date for date inputs
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.valueAsDate = new Date();
        }
    });
    
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (this.id === 'workout-form') {
                if (!handleWorkoutSubmission(this)) {
                    e.preventDefault();
                }
            }
        });
    });
});

// Utility function for API calls
async function apiCall(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        throw error;
    }
}

// Simple UI helpers; extend as needed
document.addEventListener('DOMContentLoaded', () => {
    // Example: flash auto-hide
    document.querySelectorAll('.alert').forEach(a => {
      setTimeout(() => a.classList.add('fade'), 2500);
    });
  });
  