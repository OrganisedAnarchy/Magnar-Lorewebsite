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
