import { defineComponent, h } from "vue";
import { Toaster } from "vue-sonner";

export default defineComponent({
  setup(props, { slots }) {
    return () => h("div", [
      slots.default?.(),
      h(Toaster, {
        position: "top-right",
        richColors: true,
        expand: true,
        duration: 4000,
        closeButton: true,
        toastOptions: {
          style: { fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif' }
        }
      })
    ])
  }
});
