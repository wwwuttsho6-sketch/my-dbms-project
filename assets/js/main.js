// FindIT Core Interactive Features
document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. Live Search Filter for Item Grids
    // ============================================================
    const searchInput = document.getElementById('itemSearchInput');
    const categoryFilters = document.querySelectorAll('.category-filter');
    const itemCards = document.querySelectorAll('.item-card-col');

    let currentCategory = 'all';
    let searchQuery = '';

    function filterItems() {
        let visibleCount = 0;
        itemCards.forEach(card => {
            const title    = (card.getAttribute('data-title')    || '').toLowerCase();
            const location = (card.getAttribute('data-location') || '').toLowerCase();
            const category = (card.getAttribute('data-category') || '');

            const matchesSearch   = !searchQuery || title.includes(searchQuery) || location.includes(searchQuery);
            const matchesCategory = (currentCategory === 'all' || category === currentCategory);

            if (matchesSearch && matchesCategory) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        const emptyNotice = document.getElementById('noItemsNotice');
        if (emptyNotice) {
            emptyNotice.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', e => {
            searchQuery = e.target.value.toLowerCase().trim();
            filterItems();
        });
    }

    categoryFilters.forEach(btn => {
        btn.addEventListener('click', e => {
            // Only intercept if data-category is set (client-side filter mode)
            const cat = btn.getAttribute('data-category');
            if (!cat) return;
            e.preventDefault();
            categoryFilters.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCategory = cat;
            filterItems();
        });
    });

    // ============================================================
    // 2. Image Preview Modal on Click
    // ============================================================
    document.querySelectorAll('.previewable-image').forEach(img => {
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', () => {
            const src   = img.getAttribute('src');
            const title = img.getAttribute('alt') || 'Item Image';

            // Remove any existing modal
            const old = document.getElementById('imagePreviewModal');
            if (old) old.remove();

            document.body.insertAdjacentHTML('beforeend', `
                <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-modal="true" role="dialog">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title font-heading">${title}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center p-0">
                                <img src="${src}" class="img-fluid w-100 rounded-bottom"
                                     style="max-height: 80vh; object-fit: contain;" alt="${title}">
                            </div>
                        </div>
                    </div>
                </div>`);

            const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            modal.show();

            // Clean up DOM after hide
            document.getElementById('imagePreviewModal').addEventListener('hidden.bs.modal', () => {
                document.getElementById('imagePreviewModal').remove();
            });
        });
    });

    // ============================================================
    // 3. Auto-dismiss flash alerts after 5 seconds
    // ============================================================
    document.querySelectorAll('.alert').forEach(alert => {
        // Skip alerts that have input inside them (e.g., credential info boxes)
        if (alert.querySelector('input, form')) return;
        setTimeout(() => {
            alert.style.transition = 'opacity 0.6s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 650);
        }, 5000);
    });

    // ============================================================
    // 4. Confirm dialogs for destructive action links
    //    (anchor-based deletes with onclick)
    // ============================================================
    document.querySelectorAll('a[data-confirm]').forEach(link => {
        link.addEventListener('click', e => {
            if (!confirm(link.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // ============================================================
    // 5. Active nav-link highlight on page load
    // ============================================================
    const currentPath = window.location.pathname.toLowerCase();
    document.querySelectorAll('.navbar .nav-link, .admin-header .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href !== '#' && currentPath.includes(href.split('/').pop().split('?')[0])) {
            link.classList.add('active');
        }
    });

    // ============================================================
    // 6. Form submit loader — show spinner on submit buttons
    // ============================================================
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                const orig = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Processing...';
                // Disable asynchronously on the next tick so the browser doesn't block form submission
                setTimeout(() => {
                    btn.disabled = true;
                }, 10);
                // Re-enable after 8s as failsafe
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }, 8000);
            }
        });
    });

    // ============================================================
    // 7. Table row hover highlight (ensures rows feel clickable)
    // ============================================================
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.style.cursor = 'default';
        row.addEventListener('mouseenter', () => {
            row.style.backgroundColor = 'rgba(47,47,228,0.15)';
        });
        row.addEventListener('mouseleave', () => {
            row.style.backgroundColor = '';
        });
    });

    // ============================================================
    // 8. File input preview
    // ============================================================
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                let preview = input.parentElement.querySelector('.file-preview-img');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.className = 'file-preview-img mt-2 rounded border border-primary';
                    preview.style.cssText = 'max-height: 180px; max-width: 100%; object-fit: cover; display: block;';
                    input.parentElement.appendChild(preview);
                }
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    });

});