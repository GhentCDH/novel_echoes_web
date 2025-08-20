export function useUrlGenerator(
    initRoutes: Object
){

    const routes = initRoutes

    const getRoute = (route: string) => routes[route] ?? ''

    const createTextUrl = (id: number | string) => getRoute('text_get_single').replace('text_id', id)

    return {
        getRoute,
        createTextUrl,
    }
}