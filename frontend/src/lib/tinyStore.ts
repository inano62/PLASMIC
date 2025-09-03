export function create<T extends object>(state: T){
    return new Proxy(state, {
        set(target, prop: string, value){
// @ts-ignore
            target[prop] = value;
            queueMicrotask(()=> document.querySelectorAll('[data-bind]')
                .forEach(el => el.dispatchEvent(new CustomEvent('bind:update'))));
            return true;
        }
    }) as T;
}