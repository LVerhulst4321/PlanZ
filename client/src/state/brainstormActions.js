export const SAVE_BRAINSTORM_OPTIONS = 'SAVE_BRAINSTORM_OPTIONS';

export function saveBrainstormOptions(options) {
    return {
       type: SAVE_BRAINSTORM_OPTIONS,
       payload: options
    }
 }