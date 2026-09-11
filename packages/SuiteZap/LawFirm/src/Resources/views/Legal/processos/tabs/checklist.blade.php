<div class="flex flex-col gap-4">
    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Checklist & Procedimentos
        </p>
    </div>

    <div class="w-full">
        {{-- Include the Helper Component --}}
        @include('lawfirm::components.checklist-stepper', ['processo' => $processo])
    </div>
</div>