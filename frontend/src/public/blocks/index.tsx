//src/public/blocks/index.tsx
export type Block = { type: string; data: any; sort?: number };


import Hero from './parts/Hero';
import Features from './parts/Features';
import CTA from './parts/CTA';


export function BlockRenderer({ block }: { block: Block }){
    switch(block.type){
        case 'hero': return <Hero d={block.data}/>;
        case 'features': return <Features d={block.data}/>;
        case 'cta': return <CTA d={block.data}/>;
        default: return <div style={{opacity:.7}}>Unknown block: {block.type}</div>;
    }
}