export default [
  {
    name: 'FundingSourceList',
    path: '/funding-sources',
    component: () => import('@/views/FundingSourceList.vue'),
    meta: {
      protected: true,
      permissions: ['funding-sources-manage'],
    },
  },
]
