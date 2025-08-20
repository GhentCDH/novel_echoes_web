import BaseRepository from './BaseRepository';
import type {AxiosResponse} from "axios";

class TextRepository extends BaseRepository {

    public async get(id: string): Promise<AxiosResponse> {
        const locale = this.getLocale();
        const config = this.getRequestConfig();
        return await this.axiosInstance.get(`/text/${id}`, config);
    }

    public async search(query: object): Promise<AxiosResponse> {
        const locale = this.getLocale();
        const config = this.getRequestConfig();
        return await this.axiosInstance.get(`/text/search`, {
            ...config,
            params: query,
        });
    }

    public async autocomplete(field: string, fieldFilter: string, filters: object): Promise<AxiosResponse> {
        const locale = this.getLocale();
        const config = this.getRequestConfig();
        const payload = {
            field,
            value: fieldFilter,
            filters,
        };
        return await this.axiosInstance.get(`/text/aggregation_suggest`, {
            ...config,
            params: payload,
        });
    }
}

export default new TextRepository();
