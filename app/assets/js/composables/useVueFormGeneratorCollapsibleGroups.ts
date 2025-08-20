import {onMounted, type Ref, toRef, toValue, watch} from 'vue';
import {type Schema} from './useVueFormGenerator';
import {useLocalStorage} from "@vueuse/core";

export function useVueFormGeneratorCollapsibleGroups(formSchema: Ref<Schema>, storageId: string) {

    const state = useLocalStorage(toValue(storageId), {groupIsCollapsed: [] as boolean[]}, {deep: true})
    const schema = toRef(formSchema)

    const onFormGeneratorCollapseGroup = (e) => {
        const group = e.target.parentElement;
        // get element index
        let index = Array.from(group.parentNode.children).indexOf(group)
        state.value.groupIsCollapsed[index] = state.value.groupIsCollapsed[index] !== undefined ? !state.value.groupIsCollapsed[index] : true
    }

    // make legends clickable
    const registerEvents = () => {
        const collapsableLegends: NodeListOf<HTMLLegendElement> = document.querySelectorAll('.vue-form-generator .collapsible legend');
        collapsableLegends.forEach(legend => legend.onclick = onFormGeneratorCollapseGroup);
    }

    const updateGroupClasses = () => {
        if (!Array.isArray(schema.value?.groups)) return

        schema.value.groups.forEach(function (group, index) {
            let classes = new Set((group.styleClasses || '').split(' ').filter(c => c.trim()))
            if (classes.has('collapsible')) {
                const collapsed = state.value.groupIsCollapsed[index] ?? classes.has('collapsed')
                if (collapsed) {
                    classes.add('collapsed')
                } else {
                    classes.delete('collapsed')
                }
                if (state.value.groupIsCollapsed[index] === undefined) {
                    state.value.groupIsCollapsed[index] = collapsed
                }
                group.styleClasses = [...classes].join(' ')
            }
        })
    }

    // watch for state changes
    watch(state, () => {
        updateGroupClasses()
    }, { deep: true })

    // init group classes and register events on mounted
    onMounted(() => {
        updateGroupClasses()
        registerEvents()
    })

}