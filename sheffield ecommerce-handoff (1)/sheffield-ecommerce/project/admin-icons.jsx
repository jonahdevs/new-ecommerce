// Sheffield Admin — extra icons (matches icons.jsx style: 1.6px stroke, 24-grid).
// icons.jsx loads first and provides the base <Icon> component + storefront icons.

const IconDashboard = (p) => <Icon {...p} d={<><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></>}/>;
const IconBox       = (p) => <Icon {...p} d={<><path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/></>}/>;
const IconChart     = (p) => <Icon {...p} d={<><path d="M4 20V4M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6M20 16v-2"/></>}/>;
const IconBell      = (p) => <Icon {...p} d={<><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6z"/><path d="M10 19a2 2 0 0 0 4 0"/></>}/>;
const IconSettings  = (p) => <Icon {...p} d={<><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></>}/>;
const IconEdit      = (p) => <Icon {...p} d={<><path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m14 6 4 4"/></>}/>;
const IconTrash     = (p) => <Icon {...p} d={<><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></>}/>;
const IconMoreH     = (p) => <Icon {...p} d={<><circle cx="5" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="19" cy="12" r="1.4"/></>}/>;
const IconLogout    = (p) => <Icon {...p} d={<><path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M10 12H3m0 0 3-3m-3 3 3 3"/></>}/>;
const IconAlert     = (p) => <Icon {...p} d={<><path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 17h.01"/></>}/>;
const IconTrendUp   = (p) => <Icon {...p} d={<><path d="M3 17 10 10l4 4 7-7"/><path d="M15 7h6v6"/></>}/>;
const IconTrendDown = (p) => <Icon {...p} d={<><path d="M3 7l7 7 4-4 7 7"/><path d="M15 17h6v-6"/></>}/>;
const IconCopy      = (p) => <Icon {...p} d={<><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/></>}/>;
const IconEye       = (p) => <Icon {...p} d={<><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></>}/>;
const IconTag       = (p) => <Icon {...p} d={<><path d="M3 12V5a2 2 0 0 1 2-2h7l9 9-9 9-9-9z"/><circle cx="8" cy="8" r="1.3"/></>}/>;
const IconLayers    = (p) => <Icon {...p} d={<><path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="m3 13 9 5 9-5M3 18l9 5 9-5" opacity="0.55"/></>}/>;
const IconCard      = (p) => <Icon {...p} d={<><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></>}/>;
const IconCalendar  = (p) => <Icon {...p} d={<><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/></>}/>;
const IconSort      = (p) => <Icon {...p} d={<><path d="M8 9 8 19M8 19 5 16M8 19l3-3M16 15 16 5M16 5l-3 3M16 5l3 3"/></>}/>;
const IconUsers     = (p) => <Icon {...p} d={<><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 5.2a3.2 3.2 0 0 1 0 5.6M17 14.2c2.3.7 4 2.6 4 5.8"/></>}/>;
const IconImage     = (p) => <Icon {...p} d={<><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m4 18 5-5 4 4 3-3 4 4"/></>}/>;
const IconExternal  = (p) => <Icon {...p} d={<><path d="M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/></>}/>;
const IconSave      = (p) => <Icon {...p} d={<><path d="M5 3h11l3 3v15H5z"/><path d="M8 3v5h7M8 21v-7h8v7"/></>}/>;

Object.assign(window, {
  IconDashboard, IconBox, IconChart, IconBell, IconSettings, IconEdit, IconTrash,
  IconMoreH, IconLogout, IconAlert, IconTrendUp, IconTrendDown, IconCopy, IconEye,
  IconTag, IconLayers, IconCard, IconCalendar, IconSort, IconUsers, IconImage,
  IconExternal, IconSave,
});
