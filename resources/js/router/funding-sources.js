export default [
  {
    name: 'FundingSourceList',
    path: '/funding-sources',
    component: () => import('@/views/FundingSourceList.vue'),
    meta: {
      protected: true,
      permissions: ['ep-applications-manage'],
    },
  },
]
