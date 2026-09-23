@props([])

<dialog
    id="app-confirm-dialog"
    class="app-confirm-dialog fixed inset-0 z-[100] m-auto h-fit w-[min(100%-1.5rem,24rem)] overflow-visible rounded-3xl border-0 bg-transparent p-0 shadow-none outline-none backdrop:bg-slate-950/55"
    aria-labelledby="app-confirm-title"
    aria-describedby="app-confirm-message"
>
    <div class="box-border w-full overflow-visible rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-900/30 sm:p-7">
        <div class="flex justify-center pb-2 pt-4">
            <x-ui.tapping-hand />
        </div>

        <h2 id="app-confirm-title" class="mt-6 text-center text-lg font-extrabold tracking-tight text-slate-900">
            {{ __('ui.confirm_title') }}
        </h2>
        <p id="app-confirm-message" class="mt-2 text-center text-sm leading-relaxed text-slate-500" data-confirm-message>
            {{ __('ui.confirm_default') }}
        </p>

        <div class="mt-6 flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-center sm:gap-4">
            <div class="cancel-btn-slot py-2">
                <x-ui.cancel-btn
                    type="button"
                    size="sm"
                    data-confirm-cancel
                    :label="__('ui.cancel')"
                />
            </div>
            <button
                type="button"
                data-confirm-ok
                class="inline-flex min-h-12 items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:brightness-105"
            >
                {{ __('ui.confirm_yes') }}
            </button>
        </div>
    </div>
</dialog>
