<!-- DEBUG: CHECKLIST TAB INJECTION START - 2026-02-04 10:16 -->
<div class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <!-- Header Collapsible - Removed Toggle for Stability -->
    <div class="flex items-center justify-between p-4">
        <div class="flex items-center gap-2">
            <span class="text-xl">📋</span>
            <p class="font-bold text-gray-800 dark:text-white">
                Checklist Jurídico & IA
            </p>
        </div>
    </div>

    <!-- Content -->
    <div id="checklist-body" class="border-t border-gray-200 p-4 dark:border-gray-800">
        @include('lawfirm::components.checklist-stepper', ['lead' => $lead])
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var checklistInjected = false;

        function tryInjectChecklistTab() {
            if (checklistInjected) return;

            var tabContainer = document.querySelector('.tabs ul');
            if (!tabContainer) return;

            // Check if already exists to prevent duplicates
            if (tabContainer.querySelector('.lf-checklist-tab-marker')) {
                checklistInjected = true;
                return;
            }

            // Create Tab Element
            var li = document.createElement('li');
            li.className = 'lf-checklist-tab-marker'; // Unique class to prevent duplicates
            li.innerHTML = '<a href="javascript:void(0);">📋 Checklist IA</a>';

            li.addEventListener('click', function () {
                // 1. Krayin UI: Handle active class
                var allTabs = tabContainer.querySelectorAll('li');
                allTabs.forEach(t => t.classList.remove('active'));
                li.classList.add('active');

                // 2. Krayin UI: Hide other content
                var contents = document.querySelectorAll('.content-wrapper .content');
                contents.forEach(c => c.style.display = 'none');

                // 3. Show Checklist Content
                var checklistBody = document.getElementById('checklist-body');
                if (checklistBody) {
                    checklistBody.classList.remove('lf-hidden');
                    checklistBody.style.display = 'block';
                }
            });

            tabContainer.appendChild(li);
            checklistInjected = true;
            console.log('LawFirm: Checklist Tab Injected');
        }

        // 1. Try immediately
        tryInjectChecklistTab();

        // 2. Use MutationObserver to detect when Krayin renders the tabs
        var observer = new MutationObserver(function (mutations) {
            if (checklistInjected) {
                observer.disconnect();
                return;
            }
            tryInjectChecklistTab();
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
</script>