<script>
export default {
    props: {
        iconName: {
            type: String,
            default: 'box'
        },
        width: {
            type: [Number, String],
            default: 18
        },
        height: {
            type: [Number, String],
            default: 18
        },
        iconColor: {
            type: String,
            default: 'currentColor'
        }
  },
  computed: {
      calcHeight() {
          return this.height;
      },
      calcWidth() {
          return this.width;
      }
  }
}
</script>
<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    :width="calcWidth"
    :height="calcHeight"
    viewBox="0 0 20 20"
    :aria-labelledby="iconName"
    role="presentation"
  >
    <title :id="iconName" lang="en">
      {{ iconName }}
    </title>

    <g :fill="iconColor">
      <slot>
        <path d="M11.5 11H10V7.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5H8v2H6.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM16 1H2a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zm-1 13.5a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v9z" />
      </slot>
    </g>

  </svg>
</template>