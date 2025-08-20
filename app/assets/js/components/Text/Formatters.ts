export interface IdLabel {
    id: string|number
    label: string|number
}

export interface Reference extends IdLabel {
    type: string
    locus?: string
}


export type IdLabelList = IdLabel[]
export type ReferenceList = Reference[]

export function formatCenturiesAsIdLabel(item: any): IdLabelList
{
    const centuriesMap = new Map()
    // console.log(item.works.map(w => w.centuries))
    item.works.map( w => w.centuries ).flat().forEach(c => {
        centuriesMap.set(c.id, c)
    })

    const centuries = Array.from(centuriesMap.values())
    centuries.sort((a,b) => a.order_num - b.order_num)

    const ret = centuries.map(i => {
        return {
            id: i.id,
            label: i.name
        }
    })

    ret.sort(sortIdNameByName)
    return ret
}

export function formatWorksAsIdLabel(item: any): IdLabelList
{
    if (!item.works || !Array.isArray(item.works)) {
        return []
    }
    const works = item.works.map( w => {
        return {
            id: w.id,
            label: w.name
        }
    })
    works.sort(sortIdNameByName)
    return works
}

export function formatAutorsAsIdLabel(item: any): IdLabelList
{
    if (!item.authors || !Array.isArray(item.authors)) {
        return []
    }
    const authors = item.authors.map( w => {
        return {
            id: w.id,
            label: w.name
        }
    })
    authors.sort(sortIdNameByName)
    return authors
}

export function formatReferences(item: any): ReferenceList
{
    if (!item.references || !Array.isArray(item.references)) {
        return []
    }
    const references = item.references.map( r => {
        return {
            id: r.id,
            label: r.name,
            type: r.type,
            locus: r.locus ? formatLocus(r.locus) : undefined
        }
    })

    references.sort(sortIdNameByName)
    return references
}

export function formatTextTypesAsIdLabel(item: any): IdLabelList
{
    if (!item.textTypes || !Array.isArray(item.textTypes)) {
        return []
    }
    const textTypes = item.textTypes.map( t => {
        return {
            id: t.id,
            label: t.name
        }
    })
    textTypes.sort(sortIdNameByName)
    return textTypes
}

export function getReferenceClass(item: Reference): string
{
    if (item.type === 'work') {
        return "text-italic"
    }
    return '';
}

// Sorts an array of IdLabel objects by their label property.
// use natural sort order for strings.
export function sortIdNameByName(a: IdLabel, b: IdLabel): number
{
    if (a.label < b.label) {
        return -1;
    }
    if (a.label > b.label) {
        return 1;
    }
    return 0;
}

export function formatTextTitle(item: any): string
{
    let ret = item.title;
    if (item?.work?.[0]) {
        ret += ' (' + formatLocus(item.work[0].locus) + ')';
    }

    return ret
}

export function formatLocus(locus: string): string
{
    // replace all zero characters with empty string
    return locus.replace(/0/g, '');
}