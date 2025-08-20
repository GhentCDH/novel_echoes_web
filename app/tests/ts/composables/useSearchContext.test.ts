import {describe, it, expect, beforeEach, vi} from 'vitest';
import { useSearchContext } from "@/composables/useSearchContext";
import {createMockLocation} from "../mockUrl.ts";

describe('Test useSearchContext composable', () => {
    beforeEach(() => {
        localStorage.clear();
        delete (window as any).location;
        Object.defineProperty(window, 'location', {
            configurable: true,
            writable: true,
            value: createMockLocation()
        });

    });

    it('should add some search contexts', () => {
        let hash1 = 'hash1';
        let context1 = {
            params: {"param1": "value1"},
            searchIndex: 1,
            prevUrl: "prevurl1"
        }
        let hash2 = 'hash2';
        let context2 = {
            params: {"param2": "value2"},
            searchIndex: 2,
            prevUrl: "prevurl2"
        }
        let hash3 = 'hash3';
        let context3 = {
            params: {"param3": "value3"},
            searchIndex: 3,
            prevUrl: "prevurl3"
        }

        const {
            saveContextHash,
            contextState
        } = useSearchContext();

        saveContextHash(context1, hash1);
        expect(contextState.value).toEqual({
            LRU: 'default',
            MRU: 'hash1',
            default: { data: {}, next: 'hash1' },
            hash1: {
                data: { params: {param1: "value1"}, searchIndex: 1, prevUrl: 'prevurl1' },
                next: ''
            }
        });
        saveContextHash(context2, hash2);
        expect(contextState.value).toEqual({
            LRU: 'default',
            MRU: 'hash2',
            default: { data: {}, next: 'hash1' },
            hash1: {
                data: { params: {param1: "value1"}, searchIndex: 1, prevUrl: 'prevurl1' },
                next: 'hash2'
            },
            hash2: {
                data: { params: {param2: "value2"}, searchIndex: 2, prevUrl: 'prevurl2' },
                next: ''
            }
        });
        saveContextHash(context3, hash3);
        expect(contextState.value).toEqual({
            LRU: 'default',
            MRU: 'hash3',
            default: { data: {}, next: 'hash1' },
            hash1: {
                data: { params: {param1: "value1"}, searchIndex: 1, prevUrl: 'prevurl1' },
                next: 'hash2'
            },
            hash2: {
                data: { params: {param2: "value2"}, searchIndex: 2, prevUrl: 'prevurl2' },
                next: 'hash3'
            },
            hash3: {
                data: { params: {param3: "value3"}, searchIndex: 3, prevUrl: 'prevurl3' },
                next: ''
            }
        });
    });

    it('should remove the oldest context when max contexts is reached', () => {
        let hash1 = 'hash1';
        let context1 = {
            params: {"param1": "value1"},
            searchIndex: 1,
            prevUrl: "prevurl1"
        }
        let hash2 = 'hash2';
        let context2 = {
            params: {"param2": "value2"},
            searchIndex: 2,
            prevUrl: "prevurl2"
        }
        let hash3 = 'hash3';
        let context3 = {
            params: {"param3": "value3"},
            searchIndex: 3,
            prevUrl: "prevurl3"
        }
        let hash4 = 'hash4';
        let context4 = {
            params: {"param4": "value4"},
            searchIndex: 4,
            prevUrl: "prevurl4"
        }

        const {
            saveContextHash,
            setMaxLocalStorageContexts,
            contextState
        } = useSearchContext();
        setMaxLocalStorageContexts(3);

        saveContextHash(context1, hash1);
        saveContextHash(context2, hash2);
        saveContextHash(context3, hash3);

        expect(contextState.value).toEqual({
            LRU: 'hash1',
            MRU: 'hash3',
            hash1: {
                data: { params: {param1: "value1"}, searchIndex: 1, prevUrl: 'prevurl1' },
                next: 'hash2'
            },
            hash2: {
                data: { params: {param2: "value2"}, searchIndex: 2, prevUrl: 'prevurl2' },
                next: 'hash3'
            },
            hash3: {
                data: { params: {param3: "value3"}, searchIndex: 3, prevUrl: 'prevurl3' },
                next: ''
            }
        });

        saveContextHash(context4, hash4);

        expect(contextState.value).toEqual({
            LRU: 'hash2',
            MRU: 'hash4',
            hash2: {
                data: { params: {param2: "value2"}, searchIndex: 2, prevUrl: 'prevurl2' },
                next: 'hash3'
            },
            hash3: {
                data: { params: {param3: "value3"}, searchIndex: 3, prevUrl: 'prevurl3' },
                next: 'hash4'
            },
            hash4: {
                data: { params: {param4: "value4"}, searchIndex: 4, prevUrl: 'prevurl4' },
                next: ''
            }
        });
    });

    it('should load context form the given hash', () => {
        localStorage.clear();
        let hash1 = 'hash1';
        let context1 = {
            params: {"param1": "value1"},
            searchIndex: 1,
            prevUrl: "prevurl1"
        }
        let hash2 = 'hash2';
        let context2 = {
            params: {"param2": "value2"},
            searchIndex: 2,
            prevUrl: "prevurl2"
        }
        const {
            saveContextHash,
            context,
            initContextFromUrl,
        } = useSearchContext();

        saveContextHash(context1, hash1);
        saveContextHash(context2, hash2);
        window.location.assign(`test.test/test?#hash1`);
        initContextFromUrl();
        expect(context.value.prevUrl).toEqual("prevurl1");
        expect(context.value.params).toEqual({param1: "value1"});
        expect(context.value.searchIndex).toEqual(1);
        window.location.assign(`test.test/test?#hash2`);
        initContextFromUrl();
        expect(context.value.prevUrl).toEqual("prevurl2");
        expect(context.value.params).toEqual({param2: "value2"});
        expect(context.value.searchIndex).toEqual(2);
    });
});