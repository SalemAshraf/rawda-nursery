const moods={happy:['سعيد','Happy'],calm:['هادئ','Calm'],tired:['متعب','Tired'],sad:['حزين','Sad'],unsettled:['غير مستقر','Unsettled'],sleepy:['يشعر بالنعاس','Sleepy'],good:['جيد','Good']};
export function moodLabel(value,locale){
    if(typeof value!=='string'||!Object.prototype.hasOwnProperty.call(moods,value))return value;
    return moods[value][locale==='ar'?0:1];
}
