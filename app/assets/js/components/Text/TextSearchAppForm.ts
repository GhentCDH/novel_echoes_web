import FormGeneratorFieldCreator from "@/helpers/FormGeneratorFieldCreator";
import {type Schema, type Field} from "../../composables/useVueFormGenerator";

type Translator = (key: string, params?: any) => string

export interface SchemaOptions {
    t: Translator,
}

export const createSchema = (schemaOptions: SchemaOptions): Schema => {

    const {t} = schemaOptions

    return {
        groups: [
            {
                // styleClasses: 'collapsible',
                legend: t('Filters'),
                fields: [
                    FormGeneratorFieldCreator.createMultiSelect('Century',
                        {
                            model: 'century',
                            help: t(''),
                            placeholder: t('Select a century'),
                        }
                    ),
                    FormGeneratorFieldCreator.createMultiSelect('Author',
                        {
                            model: 'author',
                            help: t(''),
                            placeholder: t('Select an author'),
                        }
                    ),
                    FormGeneratorFieldCreator.createMultiSelect('Work',
                        {
                            model: 'work',
                            help: t(''),
                            placeholder: t('Select a work'),
                        }
                    ),
                    FormGeneratorFieldCreator.createMultiSelect('Reference(s) to',
                        {
                            model: 'reference',
                            help: t(''),
                            placeholder: t('Select a reference'),
                        }
                    ),
                    FormGeneratorFieldCreator.createMultiSelect('Reference type',
                        {
                            model: 'textType',
                            help: t(''),
                            placeholder: t('Select a reference type'),
                        }
                    )
                ]
            },
        ],
    }
}