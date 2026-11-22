import { useI18n } from './i18n';
import { moodLabel } from './careLabels';
export function useFamilyCopy(){const i=useI18n();return {...i,l:(ar,en)=>i.locale.value==='ar'?ar:en,mood:value=>moodLabel(value,i.locale.value)};}
