export default [
    { name: 'cdwg-index',
        path: '/cdwgs',
        component: () => import (/* webpackChunkName "cdwgs" */ '@/views/cdwgs/CdwgIndex')
    },
    // { name: 'cdwg-detail',
    //     path: '/cdwgs/:uuid',
    //     props: true
    // }
]