<template>
    <ul class="list--inline list--links-as-badges" v-if="linklist">
        <li v-for="(link, index) in linklist"><a :href="link" class="external-link" target="_blank"><i class="fa-solid fa-link"></i></a></li>
    </ul>
</template>

<script>
export default {
    name: 'InlineLinkList',
    props: {
        linklist: {
            type: Array,
            default: []
        }
    }
}
</script>
