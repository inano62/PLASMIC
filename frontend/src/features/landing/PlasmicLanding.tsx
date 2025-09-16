import { useMemo } from "react";
import { useVertical } from "./hooks/useVertical";
import { VERTICALS } from "./model/copy";
import type { VerticalKey } from "./model/types";
import { Header } from "./components/Header";
import { Hero } from "./components/Hero";
import { Pricing } from "./components/Pricing";

import { Features } from "./components/Features";
import { Security } from "./components/Security";
import { Faq } from "./components/Faq";
import { Footer } from "./components/Footer";

export default function PlasmicLanding(props: { vertical?: VerticalKey }) {
    const vertical = useVertical(props.vertical);
    const V = useMemo(() => VERTICALS[vertical] ?? VERTICALS["default"], [vertical]);

    return (
        <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white text-slate-900">
            <Header badge={V.badge} vertical={vertical} />
            <Hero
                headline={V.headline}
                subhead={V.subhead}
                bullets={V.heroBullets}
                trust={V.trust}
                reserveHref={V.PublicReserve}
            />
            <Features features={V.features} />
            <Pricing signupHref={V.signupLink} />
            <Security />
            <Faq items={V.faq} />
            <Footer />
        </div>
    );
}
