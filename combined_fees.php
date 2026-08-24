<?php

        if ($request->paid_status === '0' && $feesId) {
            $fees = $this->fees->findById($feesId, ['*'], ['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,fees_id', 'fees_paid' => function ($q) {
                $q->withSum('compulsory_fee', 'amount')
                    ->withSum('optional_fee', 'amount');
            }]);

            $sql = $this->user->builder()->role('Student')->select('id', 'first_name', 'last_name')->with([
                'student'          => function ($query) use ($fees) {
                    $query->select('id', 'class_section_id', 'user_id')->with([
                        'class_section' => function ($query) {
                            $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name', 'section:id,name', 'medium:id,name');
                        },
                        'fees_paid' => function ($q) use ($fees) {
                            $q->where('fees_id', $fees->id)->with('compulsory_fee');
                        }
                    ]);
                }, 'optional_fees' => function ($query) {
                    $query->with('fees_class_type');
                }, 'fees_paid'     => function ($q) use ($fees) {
                    $q->where('fees_id', $fees->id)->with('compulsory_fee');
                },
                'compulsory_fees'
            ])
                ->withSum(['compulsory_fees' => function ($q) use ($fees) {
                    $q->whereHas('fees_paid', function ($q) use ($fees) {
                        $q->where('fees_id', $fees->id);
                    });
                }], 'amount')
                ->withSum(['compulsory_fees' => function($q) use($fees) {
                    $q->whereHas('fees_paid', function ($q) use ($fees) {
                        $q->where('fees_id', $fees->id);
                    });
                }], 'due_charges')
                ->whereHas('student.class_section', function ($q) use ($fees, $class_section_id, $class_id) {
                    $q->where('class_id', $fees->class_id);
                    
                    if ($class_id) {
                        $q->where('class_id', $class_id);
                    }
                    if ($class_section_id) {
                        $q->where('id', $class_section_id);
                    }
                });
                
                if (!empty($student_id)) {
                    $sql->whereHas('student', function ($q) use ($student_id) {
                        $q->where('id', $student_id);
                    });
                }

            if (!empty($_GET['search'])) {
                $search = $_GET['search'];
                $sql->where(function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%$search%")->orWhere('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                });
            }

            $currencySymbol = $settings['currency_symbol'] ?? '';

            $total_compulsory_fees = ($fees->total_compulsory_fees * $sql->count());
            $total_optional_fees = ($fees->total_optional_fees * $sql->count());
            $total_fees = $total_compulsory_fees + $total_optional_fees;
            $fees_data = [
                'total_fees' => $total_fees,
                'total_compulsory_fees' => $total_compulsory_fees,
                'total_optional_fees' => $total_optional_fees,
            ];
            $fees_data['currency_symbol'] = $currencySymbol;

            if (count($fees->fees_paid)) {
                $total_compulsory_fees_collected = $fees->fees_paid->sum('compulsory_fee_sum_amount');
                $total_optional_fees_collected = $fees->fees_paid->sum('optional_fee_sum_amount');
                $total_fees_collected = $total_compulsory_fees_collected + $total_optional_fees_collected;
                $fees_data['total_fees_collected'] = $total_fees_collected;
                $fees_data['total_compulsory_fees_collected'] = $total_compulsory_fees_collected;
                $fees_data['total_optional_fees_collected'] = $total_optional_fees_collected;
            }

            $sql->where(function ($query) use ($fees) {
                $query->where(function ($q) use ($fees) {
                    $q->whereDoesntHave('fees_paid', function ($q) use ($fees) {
                        $q->where('fees_id', $fees->id);
                    })->whereDoesntHave('student.fees_paid', function ($q) use ($fees) {
                        $q->where('fees_id', $fees->id);
                    });
                })->orWhereHas('fees_paid', function ($q) use ($fees) {
                    $q->where(['fees_id' => $fees->id, 'is_fully_paid' => 0]);
                })->orWhereHas('student.fees_paid', function ($q) use ($fees) {
                    $q->where(['fees_id' => $fees->id, 'is_fully_paid' => 0]);
                });
            });

            if ($request->month) {
                $sql->where(function ($query) use ($request, $fees) {
                    $query->whereHas('fees_paid', function ($q) use ($request, $fees) {
                        $q->whereMonth('date', $request->month)
                        ->where('fees_id',$fees->id);
                    })->orWhereHas('student.fees_paid', function ($q) use ($request, $fees) {
                        $q->whereMonth('date', $request->month)
                        ->where('fees_id',$fees->id);
                    });
                });
            }

            if ($request->payment_gateway == 'cash_cheque') {
                $sql->where(function ($query) {
                    $query->whereHas('fees_paid.compulsory_fee', function ($q) {
                        $q->whereIn('mode', ['Cash','Cheque']);
                    })->orWhereHas('student.fees_paid.compulsory_fee', function ($q) {
                        $q->whereIn('mode', ['Cash','Cheque']);
                    });
                });
            }

            if ($request->payment_gateway == 'stripe_razorpay') {
                $sql->where(function ($query) {
                    $query->whereHas('fees_paid.compulsory_fee.payment_transaction', function ($q) {
                        $q->whereIn('payment_gateway', ['Stripe','Razorpay','Flutterwave','Paystack']);
                    })->orWhereHas('student.fees_paid.compulsory_fee.payment_transaction', function ($q) {
                        $q->whereIn('payment_gateway', ['Stripe','Razorpay','Flutterwave','Paystack']);
                    });
                });
            }

            if($request->online_offline_payment) {
                $sql->where(function ($query) use ($request) {
                    $query->whereHas('fees_paid.compulsory_fee', function ($q) use ($request) {
                        if($request->online_offline_payment == 2) {
                            $q->whereIn('mode', ['Cash','Cheque']);
                        } else if ($request->online_offline_payment == 1) {
                            $q->whereIn('mode', ['Stripe','Razorpay','Flutterwave','Paystack']);
                        }
                    })->orWhereHas('student.fees_paid.compulsory_fee', function ($q) use ($request) {
                        if($request->online_offline_payment == 2) {
                            $q->whereIn('mode', ['Cash','Cheque']);
                        } else if ($request->online_offline_payment == 1) {
                            $q->whereIn('mode', ['Stripe','Razorpay','Flutterwave','Paystack']);
                        }
                    });
                });
            }

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            $no = 1;

            foreach ($res as $row) {
                $tempRow = $row->toArray();
                $fees_data['no'] = $no++;
                $tempRow['no'] = $fees_data;

                if (count($fees->installments) > 0) {
                    collect($fees->installments)->map(function ($data) use ($fees) {
                        $data['minimum_amount'] = $fees->total_compulsory_fees / count($fees->installments);
                        $data['total_amount'] = $data['minimum_amount'] + 0;
                        return $data;
                    });
                }
                $tempRow['fees'] = $fees->toArray();
                $due_date = Carbon\Carbon::parse($fees->due_date);
                $today_date = Carbon\Carbon::now()->format('Y-m-d');

                if ($due_date->gt($today_date)) {
                    $tempRow['fees_status'] = null;
                } else {
                    $tempRow['fees_status'] = 2;
                }

                $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu">';
                $operate .= '<a href="' . route('fees.compulsory.index', [$fees->id, $row->id]) . '" class="compulsory-data dropdown-item" title="' . trans('Compulsory Fees') . '"><i class="fa fa-dollar text-success mr-2"></i>' . trans('Compulsory Fees') . '</a>';

                if (count($fees->optional_fees) > 0) {
                    $operate .= '<div class="dropdown-divider"></div><a href="' . route('fees.optional.index', [$fees->id, $row->id]) . '" class="optional-data dropdown-item" title="' . trans('Optional Fees') . '"><i class="fa fa-dollar text-success mr-2"></i>' . trans('Optional Fees') . '</a>';
                }
                $operate .= '</div></div>&nbsp;&nbsp;';

                $feesPaid = $row->fees_paid;
                if (!$feesPaid && $row->student && $row->student->fees_paid->isNotEmpty()) {
                    $feesPaid = $row->student->fees_paid->first();
                    $row->setRelation('fees_paid', $feesPaid);
                }

                if (!empty($feesPaid)) {
                    $operate .= ($fees->session_year_id == $sessionYearId) ? $operate : "";
                    $operate .= BootstrapTableService::button('fa fa-file-pdf-o', route('fees.paid.receipt.pdf', $feesPaid->id), ['btn', 'btn-xs', 'btn-gradient-info', 'btn-rounded', 'btn-icon', 'generate-paid-fees-pdf'], ['target' => "_blank", 'data-id' => $feesPaid->id, 'title' => trans('generate_pdf') . ' ' . trans('fees')]);
                    $tempRow['fees_status'] = $feesPaid->is_fully_paid;
                }

                if ($feesPaid) {
                    $tempRow['paid_amount'] = $feesPaid->compulsory_fee->sum('amount');
                } else {
                    $tempRow['paid_amount'] = 0;
                }
                if ($feesPaid && isset($feesPaid->compulsory_fee[0]->mode)) {
                    $tempRow['payment_method'] = $feesPaid->compulsory_fee[0]->mode;
                }

                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        } else {
            // My NEW logic for "All" or "Paid"
            $sql = \App\Models\FeesPaid::with([
                'student' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'user_id')->with([
                        'student' => function ($q) {
                            $q->select('id', 'class_section_id', 'user_id')->with([
                                'class_section' => function ($q) {
                                    $q->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name', 'section:id,name', 'medium:id,name');
                                }
                            ]);
                        }
                    ]);
                },
                'fees' => function($q) {
                    $q->with(['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,fees_id']);
                },
                'compulsory_fee',
                'optional_fee'
            ])->where('session_year_id', $sessionYearId);

            if ($feesId) {
                $sql->where('fees_id', $feesId);
            }

            if ($class_id || $class_section_id) {
                $sql->whereHas('student.student.class_section', function ($q) use ($class_section_id, $class_id) {
                    if ($class_id) {
                        $q->where('class_id', $class_id);
                    }
                    if ($class_section_id) {
                        $q->where('id', $class_section_id);
                    }
                });
            }
            
            if (!empty($student_id)) {
                $sql->where('student_id', $student_id);
            }

            if (!empty($_GET['search'])) {
                $search = $_GET['search'];
                $sql->whereHas('student', function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%$search%")->orWhere('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                });
            }

            if ($request->paid_status != null && $request->paid_status != '') {
                if ($request->paid_status == 1) {
                    $sql->where('is_fully_paid', 1);
                } elseif ($request->paid_status == 2) {
                    $sql->where('is_fully_paid', 0);
                } elseif ($request->paid_status == 0) {
                    $sql->whereRaw('1 = 0');
                }
            }
            
            if ($request->month) {
                $sql->whereMonth('date', $request->month);
            }

            if ($request->payment_gateway == 'cash_cheque') {
                $sql->whereHas('compulsory_fee', function ($q) {
                    $q->whereIn('mode', ['Cash','Cheque']);
                });
            }

            if ($request->payment_gateway == 'stripe_razorpay') {
                $sql->whereHas('compulsory_fee.payment_transaction', function ($q) {
                    $q->whereIn('payment_gateway', ['Stripe','Razorpay','Flutterwave','Paystack']);
                });
            }

            if ($request->online_offline_payment) {
                $sql->whereHas('compulsory_fee', function ($q) use ($request) {
                    if ($request->online_offline_payment == 2) {
                        $q->whereIn('mode', ['Cash','Cheque']);
                    } else if ($request->online_offline_payment == 1) {
                        $q->whereIn('mode', ['Stripe','Razorpay','Flutterwave','Paystack']);
                    }
                });
            }

            $statsSql = clone $sql;
            $statsRows = $statsSql->with('fees:id,total_compulsory_fees,total_optional_fees')
                ->withSum('compulsory_fee', 'amount')
                ->withSum('optional_fee', 'amount')
                ->get();

            $total_compulsory_fees = $statsRows->sum(function($row) { return $row->fees ? $row->fees->total_compulsory_fees : 0; });
            $total_optional_fees = $statsRows->sum(function($row) { return $row->fees ? $row->fees->total_optional_fees : 0; });
            $total_compulsory_fees_collected = $statsRows->sum('compulsory_fee_sum_amount');
            $total_optional_fees_collected = $statsRows->sum('optional_fee_sum_amount');

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $currencySymbol = $settings['currency_symbol'] ?? '';

            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            $no = 1;

            foreach ($res as $row) {
                $feesPaid = $row;
                $student = $feesPaid->student;
                $rowFees = $feesPaid->fees;

                $tempRow = $student ? $student->toArray() : [];
                
                $fees_data = [
                    'total_fees' => $total_compulsory_fees + $total_optional_fees,
                    'total_compulsory_fees' => $total_compulsory_fees,
                    'total_optional_fees' => $total_optional_fees,
                    'total_fees_collected' => $total_compulsory_fees_collected + $total_optional_fees_collected,
                    'total_compulsory_fees_collected' => $total_compulsory_fees_collected,
                    'total_optional_fees_collected' => $total_optional_fees_collected,
                    'currency_symbol' => $currencySymbol,
                    'no' => $no++
                ];

                $tempRow['no'] = $fees_data;

                if ($rowFees && count($rowFees->installments) > 0) {
                    collect($rowFees->installments)->map(function ($data) use ($rowFees) {
                        $data['minimum_amount'] = $rowFees->total_compulsory_fees / count($rowFees->installments);
                        $data['total_amount'] = $data['minimum_amount'] + 0;
                        return $data;
                    });
                }
                
                $tempRow['fees'] = $rowFees ? $rowFees->toArray() : [];
                $tempRow['fees_status'] = $feesPaid->is_fully_paid;
                
                $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu">';
                
                if ($rowFees) {
                    $operate .= '<a href="' . route('fees.compulsory.index', [$rowFees->id, $student->id]) . '" class="compulsory-data dropdown-item" title="' . trans('Compulsory Fees') . '"><i class="fa fa-dollar text-success mr-2"></i>' . trans('Compulsory Fees') . '</a>';
                    if (count($rowFees->optional_fees) > 0) {
                        $operate .= '<div class="dropdown-divider"></div><a href="' . route('fees.optional.index', [$rowFees->id, $student->id]) . '" class="optional-data dropdown-item" title="' . trans('Optional Fees') . '"><i class="fa fa-dollar text-success mr-2"></i>' . trans('Optional Fees') . '</a>';
                    }
                }
                $operate .= '</div></div>&nbsp;&nbsp;';

                if ($feesPaid) {
                    $operate .= ($rowFees && $rowFees->session_year_id == $sessionYearId) ? $operate : "";
                    $operate .= BootstrapTableService::button('fa fa-file-pdf-o', route('fees.paid.receipt.pdf', $feesPaid->id), ['btn', 'btn-xs', 'btn-gradient-info', 'btn-rounded', 'btn-icon', 'generate-paid-fees-pdf'], ['target' => "_blank", 'data-id' => $feesPaid->id, 'title' => trans('generate_pdf') . ' ' . trans('fees')]);
                    $tempRow['fees_status'] = $feesPaid->is_fully_paid;
                }

                $tempRow['paid_amount'] = $feesPaid->compulsory_fee->sum('amount');
                if (isset($feesPaid->compulsory_fee[0]->mode)) {
                    $tempRow['payment_method'] = $feesPaid->compulsory_fee[0]->mode;
                }

                $tempRow['fees_paid'] = $feesPaid->toArray();
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }
