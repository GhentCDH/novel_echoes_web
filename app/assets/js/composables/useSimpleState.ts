import {ref, toValue, watch} from 'vue';

export function useSimpleState<T>(initialValue: T) {
    const state = ref<T>(toValue(initialValue));

    const setState = (newValue: T) => {
        state.value = newValue;
    };

    const onChange = (callback: (newValue: T) => void) => {
        return watch(state, () => callback(toValue(state)));
    };

    return {
        state,
        setState,
        onChange,
    };
}