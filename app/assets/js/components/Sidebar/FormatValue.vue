<template>
    <span v-if="value || unknown !== false">
        <template v-if="value">
            <a v-if="url" :href="url" target="_blank">{{ formatValue(value) }}</a>
            <template v-else>
                {{ formatValue(value) }}
            </template>
        </template>
        <template v-if="value == null && unknown !== false">
            {{ unknown }}
        </template>
    </span>
</template>

<script>
export default {
  name: "FormatValue",
  props: {
    value: {
      type: [String, Number, Object],
    },
    unknown: {
      type: [Boolean, String],
      default: 'unknown'
    },
    url: {
      type: String,
    },
    type: {
      type: String,
    }
  },
  methods: {
    formatValue(value) {
      if ( !value ) {
        return this.unknown
      }

      switch (this.type) {
        case 'range':
          if ( value ) {
            return (value.start === value.end ? value.start : value.start + ' - ' + value.end)
          }
          break;
        case 'id_name':
          return value.name;
          break;
        default:
          return value
      }

      return this.unknown;
    }
  }
}
</script>

<style scoped lang="scss">
</style>