<html>
    <head>
        <style>
            html {
                font-size: 12px;
            }
            body {
                font-family: arial, sans-serif;
            }
            table {
                border: solid 1px #aaa;
                border-collapse: collapse;
                font-size: 1rem;
            }

            table > thead > tr > th,
            table > tbody > tr > td {
                text-align: left;
                vertical-align: top;
                border: solid 1px  #aaa;
                padding: .75rem .5rem;
            }

            table > thead > tr > th {
                border: solid 1px  #aaa;
                border-width: 1px 1px 4px 1px;
            }


            table > tbody > tr:nth-child(odd) {
                background-color: #efefef;
            }
        </style>
    </head>
    <body>
        <h1>{{$group->displayName}}</h1>


        @if ($cois->has('1.0.0') && $cois->get('1.0.0')->count() > 0)
            <h2>Version 1.0.0 Responses</h2>
            <table style="page-break-after: always;">
                <thead>
                    <tr>
                        <th style="min-width: 10rem; max-width: 10%">Name</th>
                        <th>Do you work for a laboratory that offers fee-for-service testing related to the work of your Expert Panel?</th>
                        <th>
                            Have you made substantial contributions to the literature implicating a gene:disease relationship that relates to the work of your Expert Panel?
                        </th>
                        <th>
                            Do you have any other existing or planned independent efforts that will potentially overlap with the scope of your ClinGen work?
                        </th>
                        <th>
                            Do you have any other potential conflicts of interest to disclose (e.g. patents, intellectual property ownership, or paid consultancies related to any variants or genes associated with the work of your Expert Panel):
                        </th>
                        <th style="min-width: 6rem">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cois->get('1.0.0') as $response)
                        <tr>
                            <td>{{$response->name}}</td>
                            <td>{{$response->work_fee_lab}}</td>
                            <td>
                                {{$response->contributions_to_gd_in_ep}}
                                @if ($response->contributions_to_genes)
                                    - Genes: {{$response->contributions_to_genes}}
                                @endif
                            </td>
                            <td>
                                {{$response->independent_efforts}}
                                @if ($response->independent_efforts_details)
                                    - {{$response->independent_efforts_details}}
                                @endif
                            </td>
                            <td>
                                {{$response->coi}}
                                @if ($response->coi_details)
                                    - {{$response->coi_details}}
                                @endif
                            </td>
                            <td>
                                {{$response->completed_at}}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($cois->get('2.0.0')->count() > 0)
            <h2>Version 2.0.0 Responses</h2>
            <table style="page-break-inside: avoid;">
                <thead>
                    <tr>
                        <th style="min-width: 10rem; max-width: 10%">Name</th>
                        <th>
                            Do you work for a laboratory that offers fee-for-service testing related to the work of your ClinGen group? If this question is not applicable to your working group, please select "No."
                        </th>
                        <th>
                            Have you made substantial contributions to the literature implicating a gene:disease relationship that relates to the work of your ClinGen group?
                        </th>
                        <th>
                            Do you have any past or prospective involvement in any activity (e.g. employment, participation on an advisory board, patents, intellectual property ownership, grants or paid consultancies) that reasonably could be interpreted as a possible academic or financial conflict of interest with respect to the activities of this ClinGen group?
                        </th>
                        <th style="min-width: 6rem">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cois->get('2.0.0') as $response)
                        <tr>
                            <td>{{$response->name}}</td>
                            <td>{{$response->work_fee_lab}}</td>
                            <td>
                                {{$response->contributions_to_gd_in_group}}
                                @if ($response->contributions_to_genes)
                                    - Genes: {{$response->contributions_to_genes}}
                                @endif
                            </td>
                            <td>
                                {{$response->coi}}
                                @if ($response->coi_details)
                                    - {{$response->coi_details ?? null}}
                                @endif
                            </td>
                            <td>
                                {{$response->completed_at}}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </body>
</html>
