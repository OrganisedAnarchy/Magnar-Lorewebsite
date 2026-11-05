<script setup>
import { dismissToast, toasts } from './toast.js';
</script>

<template>
    <div class="toast-region" aria-live="polite" aria-atomic="false">
        <transition-group name="toast" tag="div" class="toast-list">
            <div
                v-for="item in toasts"
                :key="item.id"
                class="toast"
                :class="`toast-${item.type}`"
                :role="item.type === 'error' ? 'alert' : 'status'"
            >
                <span class="toast-message">{{ item.message }}</span>
                <button type="button" class="toast-close" aria-label="Dismiss notification" @click="dismissToast(item.id)">✕</button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-region { position: fixed; top: 1rem; right: 1rem; z-index: 1000; width: min(22rem, calc(100vw - 2rem)); pointer-events: none; }
.toast-list { display: flex; flex-direction: column; gap: .5rem; }
.toast { display: flex; align-items: flex-start; gap: .75rem; padding: .7rem .9rem; background: #1f1b2b; color: #e8e4f0; border: 1px solid #3a3450; border-left-width: 4px; border-radius: 6px; box-shadow: 0 6px 20px rgba(0, 0, 0, .45); pointer-events: auto; }
.toast-success { border-left-color: #3fb87a; }
.toast-error { border-left-color: #ff5a6e; }
.toast-info { border-left-color: #6b4fd8; }
.toast-message { flex: 1; font-size: .95rem; }
.toast-close { padding: 0; background: none; border: 0; color: #9a93ad; cursor: pointer; font: inherit; }
.toast-close:hover { color: #fff; }
.toast-enter-active, .toast-leave-active { transition: opacity .2s ease, transform .2s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(1.5rem); }
</style>
