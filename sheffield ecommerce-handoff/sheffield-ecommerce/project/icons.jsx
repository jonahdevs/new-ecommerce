// Sheffield — tiny icon library (1.5px stroke, 20×20 default).

const Icon = ({ d, size = 20, stroke = "currentColor", fill = "none", sw = 1.6, style }) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill={fill} stroke={stroke} strokeWidth={sw} strokeLinecap="round" strokeLinejoin="round" style={style}>
    {d}
  </svg>
);

const IconSearch    = (p) => <Icon {...p} d={<><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></>}/>;
const IconCart      = (p) => <Icon {...p} d={<><path d="M3 4h2l2.5 12.5a2 2 0 0 0 2 1.5h7a2 2 0 0 0 2-1.6L20.5 8H6"/><circle cx="9" cy="21" r="1.4"/><circle cx="18" cy="21" r="1.4"/></>}/>;
const IconUser      = (p) => <Icon {...p} d={<><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></>}/>;
const IconHeart     = (p) => <Icon {...p} d={<><path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9z"/></>}/>;
const IconArrow     = (p) => <Icon {...p} d={<><path d="M5 12h14M13 5l7 7-7 7"/></>}/>;
const IconArrowL    = (p) => <Icon {...p} d={<><path d="M19 12H5M11 5l-7 7 7 7"/></>}/>;
const IconClose     = (p) => <Icon {...p} d={<><path d="M5 5l14 14M19 5L5 19"/></>}/>;
const IconMenu      = (p) => <Icon {...p} d={<><path d="M3 6h18M3 12h18M3 18h18"/></>}/>;
const IconChevron   = (p) => <Icon {...p} d={<><path d="m6 9 6 6 6-6"/></>}/>;
const IconChevronR  = (p) => <Icon {...p} d={<><path d="m9 6 6 6-6 6"/></>}/>;
const IconCheck     = (p) => <Icon {...p} d={<><path d="m5 12 5 5L20 7"/></>}/>;
const IconStar      = (p) => <Icon {...p} d={<><path d="m12 3 2.7 5.7 6.3.9-4.5 4.4 1.1 6.3L12 17l-5.6 3.3 1.1-6.3L3 9.6l6.3-.9L12 3z"/></>}/>;
const IconStarFill  = (p) => <Icon {...p} fill="currentColor" stroke="none" d={<path d="m12 3 2.7 5.7 6.3.9-4.5 4.4 1.1 6.3L12 17l-5.6 3.3 1.1-6.3L3 9.6l6.3-.9L12 3z"/>}/>;
const IconFilter    = (p) => <Icon {...p} d={<><path d="M3 5h18M6 12h12M10 19h4"/></>}/>;
const IconGrid      = (p) => <Icon {...p} d={<><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></>}/>;
const IconRows      = (p) => <Icon {...p} d={<><rect x="3" y="4" width="18" height="6"/><rect x="3" y="14" width="18" height="6"/></>}/>;
const IconTruck     = (p) => <Icon {...p} d={<><path d="M3 7h11v9H3zM14 11h4l3 3v2h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></>}/>;
const IconShield    = (p) => <Icon {...p} d={<><path d="M12 3 4 6v6c0 4 3 7 8 9 5-2 8-5 8-9V6l-8-3z"/></>}/>;
const IconChat      = (p) => <Icon {...p} d={<><path d="M21 12a8 8 0 1 1-3.2-6.4L21 4l-1.5 4.4A8 8 0 0 1 21 12z"/></>}/>;
const IconDocument  = (p) => <Icon {...p} d={<><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/></>}/>;
const IconDownload  = (p) => <Icon {...p} d={<><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></>}/>;
const IconShare     = (p) => <Icon {...p} d={<><circle cx="6" cy="12" r="2.4"/><circle cx="18" cy="6" r="2.4"/><circle cx="18" cy="18" r="2.4"/><path d="m8 11 8-4M8 13l8 4"/></>}/>;
const IconPlus      = (p) => <Icon {...p} d={<><path d="M12 5v14M5 12h14"/></>}/>;
const IconMinus     = (p) => <Icon {...p} d={<><path d="M5 12h14"/></>}/>;
const IconCompare   = (p) => <Icon {...p} d={<><path d="M3 6h7M3 18h7M14 6h7M14 18h7"/><circle cx="3.5" cy="12" r="1"/><circle cx="20.5" cy="12" r="1"/><path d="M3.5 6v12M20.5 6v12"/></>}/>;
const IconPhone     = (p) => <Icon {...p} d={<><path d="M5 4h4l2 5-3 2a12 12 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></>}/>;
const IconMail      = (p) => <Icon {...p} d={<><rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m4 7 8 6 8-6"/></>}/>;
const IconLocation  = (p) => <Icon {...p} d={<><path d="M12 22s-7-7-7-12a7 7 0 0 1 14 0c0 5-7 12-7 12z"/><circle cx="12" cy="10" r="2.5"/></>}/>;
const IconWrench    = (p) => <Icon {...p} d={<><path d="M14 7a4 4 0 1 0 4 4L20 9l-2-2-2 2zM13 11l-9 9 2 2 9-9"/></>}/>;
const IconFire      = (p) => <Icon {...p} d={<><path d="M12 3s5 4 5 9a5 5 0 0 1-10 0c0-2 1-3 1-4 0 1 1 2 2 2-2-3 1-5 2-7z"/></>}/>;
const IconCertified = (p) => <Icon {...p} d={<><circle cx="12" cy="9" r="6"/><path d="m9 9 2 2 4-4M9 15l-2 6 5-3 5 3-2-6"/></>}/>;
const IconLeaf      = (p) => <Icon {...p} d={<><path d="M4 20c0-9 7-16 16-16 0 9-7 16-16 16zM4 20l8-8"/></>}/>;
const IconClock     = (p) => <Icon {...p} d={<><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></>}/>;

Object.assign(window, {
  IconSearch, IconCart, IconUser, IconHeart, IconArrow, IconArrowL,
  IconClose, IconMenu, IconChevron, IconChevronR, IconCheck, IconStar, IconStarFill,
  IconFilter, IconGrid, IconRows, IconTruck, IconShield, IconChat, IconDocument,
  IconDownload, IconShare, IconPlus, IconMinus, IconCompare, IconPhone, IconMail,
  IconLocation, IconWrench, IconFire, IconCertified, IconLeaf, IconClock,
});
