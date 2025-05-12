// Modern Gelir-Gider Takip Sistemi JavaScript - Light Theme Only

// DOM yüklendiğinde çalışacak fonksiyonlar
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeTooltips();
    initializeAnimations();
    initializeCharts();
    initializeFormValidation();
    initializeNotifications();
    initializeModals();
    
    // Counter animations for dashboard
    animateCounters();
    
    // Smooth scrolling
    initializeSmoothScroll();
    
    // Search functionality
    initializeSearch();
    
    // Floating action button
    initializeFAB();
    
    // Initialize other features
    initializeCategoryColorPicker();
    initializeTransactionFilters();
    initializeDeleteConfirmation();
    animateProgress();
    initializeLazyLoading();
    initializeKeyboardShortcuts();
});

// Tooltip initialization
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            animation: true,
            delay: { show: 500, hide: 100 }
        });
    });
}

// Page load animations
function initializeAnimations() {
    // Fade in elements
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.animation = `fadeIn 0.5s ease-out forwards ${index * 0.1}s`;
    });
    
    // Slide up elements
    const slideElements = document.querySelectorAll('.slide-up');
    slideElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.animation = `slideUp 0.5s ease-out forwards ${index * 0.1}s`;
    });
    
    // Add stagger animation to table rows
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.animation = `fadeIn 0.3s ease-out forwards ${index * 0.05}s`;
    });
}

// Counter animations
function animateCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    
    const observerOptions = {
        threshold: 0.5
    };
    
    const observerCallback = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseFloat(counter.getAttribute('data-counter'));
                const duration = 2000; // 2 seconds
                const steps = 60;
                const stepValue = target / steps;
                let current = 0;
                
                const timer = setInterval(() => {
                    current += stepValue;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    
                    // Format currency
                    if (counter.dataset.format === 'currency') {
                        counter.textContent = formatMoney(current);
                    } else {
                        counter.textContent = Math.round(current);
                    }
                }, duration / steps);
                
                observer.unobserve(counter);
            }
        });
    };
    
    const observer = new IntersectionObserver(observerCallback, observerOptions);
    counters.forEach(counter => observer.observe(counter));
}

// Chart initialization with modern styling
function initializeCharts() {
    // Set default font
    Chart.defaults.font.family = "'Inter', sans-serif";
    
    // Category distribution chart
    const categoryChartElement = document.getElementById('categoryChart');
    if (categoryChartElement && typeof categoryLabels !== 'undefined') {
        const ctx = categoryChartElement.getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: [
                        '#4F46E5',
                        '#10B981',
                        '#EF4444',
                        '#F59E0B',
                        '#3B82F6',
                        '#6366F1',
                        '#EC4899',
                        '#14B8A6',
                        '#8B5CF6',
                        '#F43F5E'
                    ],
                    borderWidth: 0,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1F2937',
                        bodyColor: '#4B5563',
                        borderColor: '#E5E7EB',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        caretSize: 0,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: ${formatMoney(value)} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
    
    // Monthly trend chart
    const monthlyChartElement = document.getElementById('monthlyChart');
    if (monthlyChartElement && typeof months !== 'undefined') {
        const ctx = monthlyChartElement.getContext('2d');
        
        // Create gradients
        const gradientIncome = ctx.createLinearGradient(0, 0, 0, 300);
        gradientIncome.addColorStop(0, 'rgba(16, 185, 129, 0.8)');
        gradientIncome.addColorStop(1, 'rgba(16, 185, 129, 0.1)');
        
        const gradientExpense = ctx.createLinearGradient(0, 0, 0, 300);
        gradientExpense.addColorStop(0, 'rgba(239, 68, 68, 0.8)');
        gradientExpense.addColorStop(1, 'rgba(239, 68, 68, 0.1)');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months.map(month => {
                    const [year, monthNum] = month.split('-');
                    return new Date(year, monthNum - 1).toLocaleDateString('tr-TR', { month: 'short', year: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Gelir',
                        data: incomeData,
                        backgroundColor: gradientIncome,
                        borderColor: '#10B981',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8
                    },
                    {
                        label: 'Gider',
                        data: expenseData,
                        backgroundColor: gradientExpense,
                        borderColor: '#EF4444',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            padding: 20,
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1F2937',
                        bodyColor: '#4B5563',
                        borderColor: '#E5E7EB',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        titleSpacing: 8,
                        bodySpacing: 8,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${formatMoney(context.parsed.y)}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            color: '#6B7280'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(229, 231, 235, 0.5)',
                            drawBorder: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            color: '#6B7280',
                            callback: function(value) {
                                return formatMoney(value);
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
 }
 
 // Enhanced form validation
 function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
            
            // Animate invalid fields
            const invalidFields = form.querySelectorAll(':invalid');
            invalidFields.forEach(field => {
                field.addEventListener('animationend', () => {
                    field.style.animation = '';
                });
                field.style.animation = 'shake 0.5s ease';
            });
        }, false);
    });
    
    // Real-time validation with visual feedback
    const inputs = document.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                if (this.checkValidity()) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                } else {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    });
 }
 
 // Toast notifications
 function initializeNotifications() {
    window.showNotification = function(message, type = 'success', duration = 3000) {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        const icons = {
            success: 'bi-check-circle',
            danger: 'bi-exclamation-circle',
            warning: 'bi-exclamation-triangle',
            info: 'bi-info-circle'
        };
        
        const toastId = 'toast-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center">
                        <i class="bi ${icons[type]} me-2" style="font-size: 1.25rem;"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: duration
        });
        
        // Animate in
        toastElement.style.transform = 'translateX(100%)';
        toastElement.style.transition = 'transform 0.3s ease-out';
        
        setTimeout(() => {
            toastElement.style.transform = 'translateX(0)';
        }, 100);
        
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.style.transform = 'translateX(100%)';
            setTimeout(() => {
                toastElement.remove();
            }, 300);
        });
    };
 }
 
 // Enhanced modal animations
 function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            this.querySelector('.modal-dialog').style.transform = 'scale(0.9)';
            this.querySelector('.modal-dialog').style.opacity = '0';
        });
        
        modal.addEventListener('shown.bs.modal', function() {
            const modalDialog = this.querySelector('.modal-dialog');
            modalDialog.style.transition = 'all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            modalDialog.style.transform = 'scale(1)';
            modalDialog.style.opacity = '1';
            
            // Focus first input
            const firstInput = modalDialog.querySelector('input, select, textarea');
            if (firstInput) {
                firstInput.focus();
            }
        });
        
        modal.addEventListener('hide.bs.modal', function() {
            const modalDialog = this.querySelector('.modal-dialog');
            modalDialog.style.transform = 'scale(0.9)';
            modalDialog.style.opacity = '0';
        });
    });
 }
 
 // Smooth scrolling
 function initializeSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navbarHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
 }
 
 // Live search functionality
 function initializeSearch() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;
    
    let searchTimeout;
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.toLowerCase();
        
        searchTimeout = setTimeout(() => {
            const searchableElements = document.querySelectorAll('[data-searchable]');
            let hasResults = false;
            
            searchableElements.forEach(element => {
                const text = element.textContent.toLowerCase();
                const parent = element.closest('.search-item') || element.closest('tr');
                
                if (text.includes(query) || query === '') {
                    parent.style.display = '';
                    parent.style.opacity = '0';
                    parent.style.animation = 'fadeIn 0.3s ease forwards';
                    hasResults = true;
                } else {
                    parent.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            const noResultsMessage = document.getElementById('noResults');
            if (!hasResults && query !== '') {
                if (!noResultsMessage) {
                    const message = document.createElement('div');
                    message.id = 'noResults';
                    message.className = 'empty-state';
                    message.innerHTML = `
                        <i class="bi bi-search"></i>
                        <h5>Sonuç bulunamadı</h5>
                        <p>Arama kriterlerinize uygun sonuç bulunamadı.</p>
                    `;
                    searchInput.closest('.search-container').appendChild(message);
                }
            } else if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }, 300);
    });
 }
 
 // Enhanced Floating Action Button
 function initializeFAB() {
    const fab = document.createElement('button');
    fab.className = 'fab';
    fab.innerHTML = '<i class="bi bi-plus-lg"></i>';
    fab.onclick = () => window.location.href = 'add-transaction.php';
    document.body.appendChild(fab);
    
    // Add ripple effect
    fab.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
    
    // Hide FAB on scroll
    let lastScroll = 0;
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll <= 0) {
            fab.classList.remove('hidden');
        } else if (currentScroll > lastScroll && !fab.classList.contains('hidden')) {
            fab.classList.add('hidden');
        } else if (currentScroll < lastScroll && fab.classList.contains('hidden')) {
            fab.classList.remove('hidden');
        }
        
        lastScroll = currentScroll;
    });
 }
 
 // Utility Functions
 function formatMoney(amount) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
        minimumFractionDigits: 2
    }).format(amount);
 }
 
 function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
 }
 
 // Category color picker with preview
 function initializeCategoryColorPicker() {
    const colorInputs = document.querySelectorAll('input[type="color"]');
    
    colorInputs.forEach(input => {
        // Create color preview
        const preview = document.createElement('div');
        preview.className = 'color-preview';
        preview.style.width = '30px';
        preview.style.height = '30px';
        preview.style.borderRadius = '6px';
        preview.style.backgroundColor = input.value;
        preview.style.border = '2px solid #e5e7eb';
        
        input.parentElement.insertBefore(preview, input);
        input.style.display = 'none';
        
        preview.addEventListener('click', () => input.click());
        
        input.addEventListener('change', function() {
            preview.style.backgroundColor = this.value;
        });
    });
 }
 
 // Transaction filters with animation
 function initializeTransactionFilters() {
    const filterButtons = document.querySelectorAll('[data-filter]');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            const transactions = document.querySelectorAll('.transaction-item');
            
            // Update active button
            filterButtons.forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
                btn.classList.add('btn-light');
            });
            this.classList.remove('btn-light');
            this.classList.add('active', 'btn-primary');
            
            // Filter transactions with animation
            transactions.forEach((transaction, index) => {
                const type = transaction.dataset.type;
                
                if (filter === 'all' || type === filter) {
                    transaction.style.display = '';
                    transaction.style.opacity = '0';
                    transaction.style.animation = `fadeIn 0.3s ease forwards ${index * 0.05}s`;
                } else {
                    transaction.style.opacity = '0';
                    setTimeout(() => {
                        transaction.style.display = 'none';
                    }, 300);
                }
            });
            
            // Update summary
            updateFilterSummary(filter);
        });
    });
 }
 
 // Update filter summary
 function updateFilterSummary(filter) {
    const summaryElement = document.getElementById('filterSummary');
    if (!summaryElement) return;
    
    const visibleTransactions = document.querySelectorAll(`.transaction-item${filter !== 'all' ? `[data-type="${filter}"]` : ''}`);
    const totalAmount = Array.from(visibleTransactions).reduce((sum, item) => {
        const amount = parseFloat(item.dataset.amount) || 0;
        return sum + amount;
    }, 0);
    
    summaryElement.innerHTML = `
        <small class="text-muted">
            Görüntülenen: <strong>${visibleTransactions.length}</strong> işlem, 
            Toplam: <strong>${formatMoney(totalAmount)}</strong>
        </small>
    `;
 }
 
 // Enhanced delete confirmation
 function initializeDeleteConfirmation() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-delete')) {
            e.preventDefault();
            const button = e.target.closest('.btn-delete');
            const href = button.getAttribute('href');
            const itemName = button.dataset.itemName || 'bu öğeyi';
            
            Swal.fire({
                title: 'Emin misiniz?',
                text: `${itemName} silmek istediğinize emin misiniz?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Evet, sil!',
                cancelButtonText: 'İptal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        }
    });
 }
 
 // Progress bar animations
 function animateProgress() {
    const progressBars = document.querySelectorAll('.progress-bar');
    
    const observerOptions = {
        threshold: 0.5
    };
    
    const observerCallback = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const bar = entry.target;
                const target = bar.getAttribute('aria-valuenow');
                bar.style.width = target + '%';
                observer.unobserve(bar);
            }
        });
    };
    
    const observer = new IntersectionObserver(observerCallback, observerOptions);
    progressBars.forEach(bar => {
        bar.style.width = '0%';
        observer.observe(bar);
    });
 }
 
 // Lazy loading for images
 function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('fade-in');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
 }
 
 // Keyboard shortcuts
 function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + N: New transaction
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = 'add-transaction.php';
        }
        
        // Ctrl/Cmd + K: Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape: Close modals
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                bootstrap.Modal.getInstance(modal).hide();
            });
        }
    });
 }
 
 // Add shake animation for invalid fields
 const style = document.createElement('style');
 style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: scale(0);
        animation: ripple 0.6s linear;
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
 `;
 document.head.appendChild(style);
 
 // Export functions for use in other scripts
 window.appUtils = {
    formatMoney,
    formatDate,
    showNotification,
    animateCounters,
    animateProgress
 };