export type HeroData = {
    kicker?: string;
    title?: string;
    subtitle?: string;
    btnText?: string;
    btnHref?: string;
    headline?: string;
    imgUrl? : any;
    imageUrl?:any;
    bgUrl?:any;
    avatarUrl:any;
};
export type FeaturesData = {
    items?: { title?: string; text?: string }[];
};
export type CtaData = { text?: string; btnText?: string; btnHref?: string };

export type Block = { type: string; data: any; sort?: number };
