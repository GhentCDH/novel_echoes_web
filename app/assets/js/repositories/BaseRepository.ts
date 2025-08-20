import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';
import qs from 'qs';
import { i18n } from '@/locales/i18n';

export default class BaseRepository {
    protected axiosInstance: AxiosInstance;
    protected abortController: AbortController | null;

    constructor() {
        this.axiosInstance = axios.create({
            paramsSerializer: params => qs.stringify(params),
        });
        this.abortController = null;
    }

    protected getLocale(): string {
        return i18n.global.locale.value;
    }

    protected getRequestConfig(): AxiosRequestConfig {
        // if (this.abortController) {
        //     this.abortController.abort();
        // }
        // this.abortController = new AbortController();
        return {
            // signal: this.abortController.signal,
        };
    }
}