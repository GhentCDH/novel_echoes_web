<template>
    <div class="widget" :class="{collapsed: collapsed, collapsible: collapsible}">
        <div class="sticky-block" @click="toggleCollapsed()">
            <div class="title">
                <span class="toggle__collapsed" >
                    <i class="fa fa-angle-down" aria-hidden="true"></i>
                </span>
                <span>{{ title }}
                    <span class="count" v-if="count >= 0">{{ count }}</span>
                </span>
            </div>
        </div>
        <div class="body" :class="{fixed: isFixed}">
            <slot></slot>
        </div>
    </div>
</template>

<script>
export default {
    name: "Widget",
    props: {
        title: {
            type: String,
            required: true
        },
        count: {
            type: Number,
            required: false
        },
        isFixed: {
            type: Boolean,
            default: false
        },
        collapsed: {
            type: Boolean,
            default: false
        },
        collapsible: {
            type: Boolean,
            default: true
        }
    },
    methods: {
        toggleCollapsed: function() {
            this.$emit('update:collapsed', !this.collapsed)
        },
    },
}
</script>

<style scoped lang="scss">
@import '../../../scss/init';
.widget {

  .sticky-block {
  }

  .title {
    text-transform: uppercase;
    font-size: 18px;
    cursor: pointer;
    padding: 12px 0 7px;
    letter-spacing: 0.2rem;

    font-family: $default-font-family, Arial, sans-serif;
    color: #777;

    .toggle__collapsed {
      display: none;
    }

    .count {
      border: 1px solid #eee;
      padding: 3px 5px;
      border-radius: 5px;
      font-size: 80%;
      color: #aaa;
      position: relative;
      top: 0;
      margin-right: 0.5em;
      letter-spacing: 0;
      line-height: 1;
      float: right;
    }
  }

  .body {
    padding: 15px 0;

    &.fixed {
      max-height: 200px;
      overflow-y: auto;
    }
  }

  .form-group {
    margin-bottom: 5px;

    .checkbox, .radio {
      margin-top: 0;
      margin-bottom: 0;
    }
  }

  &.collapsible {

    .toggle__collapsed {
      display: block;
      float: right;
      color: #cccccc;

      .fa {
        transform: rotate(180deg)
      }
    }

    &.collapsed {

      .toggle__collapsed .fa {
        transform: none;
      }

      .body {
        max-height: 0;
        overflow: hidden;
        transition: 0.2s;
        margin: 0;
        padding: 0;
      }
    }
  }
}

</style>