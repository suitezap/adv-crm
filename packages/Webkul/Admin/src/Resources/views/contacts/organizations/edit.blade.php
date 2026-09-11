
<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.contacts.organizations.edit.title')
    </x-slot>

    {!! view_render_event('admin.organizations.edit.form.before') !!}

    <x-admin::form
        :action="route('admin.contacts.organizations.update', $organization->id)"
        method="PUT"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    {!! view_render_event('admin.organizations.edit.breadcrumbs.before', ['organization' => $organization]) !!}

                    <x-admin::breadcrumbs 
                        name="contacts.organizations.edit" 
                        :entity="$organization"
                    />

                    {!! view_render_event('admin.organizations.edit.breadcrumbs.before', ['organization' => $organization]) !!}

                    <div class="text-xl font-bold dark:text-gray-300">
                        @lang('admin::app.contacts.organizations.edit.title')
                    </div>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <div class="flex items-center gap-x-2.5">
                        {!! view_render_event('admin.organizations.edit.save_button.before', ['organization' => $organization]) !!}

                        <!-- Save button for person -->
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.contacts.organizations.edit.save-btn')
                        </button>

                        {!! view_render_event('admin.organizations.edit.save_button.after', ['organization' => $organization]) !!}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">
                <!-- Left Section: Basic Fields -->
                <div class="flex flex-col gap-2">
                    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        {!! view_render_event('admin.contacts.organizations.edit.form_controls.before') !!}

                        <x-admin::attributes
                            :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                ['code', 'IN', ['name', 'emails', 'contact_numbers', 'user_id']],
                                'entity_type' => 'organizations',
                            ])"
                            :custom-validations="[
                                'name' => [
                                    'max:100',
                                ],
                            ]"
                            :entity="$organization"
                        />
                    </div>
                </div>

                <!-- Right Section: Advanced Fields -->
                <div class="flex flex-col gap-2">
                    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-4 flex items-center justify-between">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                Dados da Empresa Avançados
                            </p>
                        </div>

                        <x-admin::attributes
                            :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                ['code', 'NOTIN', ['name', 'emails', 'contact_numbers', 'user_id']],
                                'entity_type' => 'organizations',
                            ])"
                            :custom-validations="[
                                'address' => [
                                    'max:100',
                                ],
                                'postcode' => [
                                    'postcode',
                                ],
                            ]"
                            :entity="$organization"
                        />
                        
                        {!! view_render_event('admin.contacts.organizations.edit.form_controls.after') !!}
                    </div>
                </div>
            </div>
        </div>
    </x-admin::form>

    {!! view_render_event('admin.organizations.edit.form.after') !!}
</x-admin::layouts>
