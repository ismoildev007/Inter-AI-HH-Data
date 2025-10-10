<div id="admin-back-button-config"
     data-fallback-url="{{ route('admin.dashboard') }}"
     style="display:none;"></div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const config = document.getElementById('admin-back-button-config');
        if (!config) {
            return;
        }
        const fallbackUrl = config.dataset.fallbackUrl || '/';
        const pageHeader = document.querySelector('.page-header');
        if (!pageHeader) {
            return;
        }

        const existing = pageHeader.querySelector('.breadcrumb-back');
        if (existing) {
            return;
        }

        let breadcrumb = pageHeader.querySelector('.breadcrumb');
        if (!breadcrumb) {
            breadcrumb = document.createElement('ul');
            breadcrumb.className = 'breadcrumb';
            pageHeader.appendChild(breadcrumb);
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary btn-sm d-inline-flex align-items-center breadcrumb-back';
        button.innerHTML = '<i class="feather-arrow-left me-1"></i>Back';
        button.addEventListener('click', function (e) {
            e.preventDefault();
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = fallbackUrl;
            }
        });

        let rightSection = pageHeader.querySelector('.page-header-right');
        if (!rightSection) {
            rightSection = document.createElement('div');
            rightSection.className = 'page-header-right ms-auto d-flex align-items-center gap-2';
            pageHeader.appendChild(rightSection);
        } else {
            rightSection.classList.add('d-flex', 'align-items-center');
            if (!rightSection.classList.contains('gap-2')) {
                rightSection.classList.add('gap-2');
            }
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'page-header-back-btn';
        wrapper.appendChild(button);
        rightSection.appendChild(wrapper);
    });
</script>
