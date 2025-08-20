import {vi} from "vitest";

const baseHref = 'http://test.com/';

export const createMockLocation = () => {
    let _href = baseHref;
    const updateFromHref = (newHref: string) => {
        _href = newHref;
        try {
            // Use the URL constructor to parse the newHref relative to baseHref if needed
            const url = new URL(newHref, baseHref);
            // Update all the properties based on the parsed URL
            mockLocation.protocol = url.protocol;
            mockLocation.host = url.host;
            mockLocation.hostname = url.hostname;
            mockLocation.port = url.port;
            mockLocation.pathname = url.pathname;
            mockLocation.search = url.search;
            mockLocation.hash = url.hash;
        } catch (error) {
            // Fallback: if URL parsing fails, simply set hash and search empty.
            mockLocation.protocol = '';
            mockLocation.host = '';
            mockLocation.hostname = '';
            mockLocation.port = '';
            mockLocation.pathname = newHref;
            mockLocation.search = '';
            mockLocation.hash = '';
        }
    };

    const mockLocation: any = {
        // Initial values
        _href,
        get href() {
            return this._href;
        },
        set href(newHref: string) {
            updateFromHref(newHref);
        },
        protocol: 'http:',
        host: 'test.com',
        hostname: 'test.com',
        port: '',
        pathname: '/',
        search: '',
        hash: '',
        assign: vi.fn(function (url: string) {
            // Simulate navigation by updating href and all properties.
            updateFromHref(url);
        }),
        replace: vi.fn(function (url: string) {
            // You may simulate replace the same way or differently if needed.
            updateFromHref(url);
        }),
        reload: vi.fn(),
        toString() {
            return this.href;
        }
    };

    // Initialize properties from the initial _href value:
    updateFromHref(_href);

    return mockLocation;
};