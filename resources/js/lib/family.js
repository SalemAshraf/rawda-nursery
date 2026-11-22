import { useI18n } from './i18n';
export function useFamilyCopy(){const i=useI18n();return {...i,l:(ar,en)=>i.locale.value==='ar'?ar:en};}
