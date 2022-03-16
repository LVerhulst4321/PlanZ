export const SAVE_OPTIONS = 'SAVE_OPTIONS';

export function saveOptions(options) {
    return {
       type: SAVE_OPTIONS,
       payload: options
    }
 }